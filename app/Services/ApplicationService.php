<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Models\ApplicantChange;
use App\Models\Application;
use App\Models\Program;
use App\Models\Requirement;
use App\Models\User;
use App\Support\ApplicantForm;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Applicant registration and the application lifecycle (FR-01, FR-02, FR-05, FR-10).
 */
class ApplicationService
{
    public function __construct(
        private ApplicationStatusService $status,
        private AuditLogger $audit,
    ) {}

    /** New applicant account: user + profile + draft application for the open period. */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $user->assignRole('applicant');

            $applicant = Applicant::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
            ]);

            $application = $this->start($applicant, $user);
            $this->audit->record('applicant.registered', 'Created an applicant account', $applicant, $application, actor: $user);

            return $user;
        });
    }

    public function start(Applicant $applicant, ?User $by = null): Application
    {
        $application = Application::create([
            'number' => 'tmp-'.Str::random(16),   // 20 chars: fits the column until the real number is set
            'applicant_id' => $applicant->id,
            'admission_period_id' => AdmissionPeriod::current()->id,
            'status' => ApplicationStatus::Draft,
            'form_progress' => ['step' => 1, 'completed' => []],
        ]);
        // The number comes from the row id, so it's unique without a race
        $application->update(['number' => sprintf('%s-%s-%05d', config('admission.number_prefix'), now()->year, $application->id)]);
        $this->status->recordCreated($application, $by);

        return $application;
    }

    /** The applicant's application for the open period, created if missing. */
    public function current(Applicant $applicant): Application
    {
        $period = AdmissionPeriod::current();

        return $applicant->applications()->where('admission_period_id', $period->id)->first()
            ?? $this->start($applicant, $applicant->user);
    }

    /**
     * Autosave from "My application". Values that fail their format rule are
     * skipped and reported; sections newly marked complete are validated
     * strictly. Returns the skipped fields with their messages.
     *
     * @return array<string, string>
     */
    public function saveForm(Application $application, array $input, User $by): array
    {
        if (! $this->isEditable($application)) {
            throw new BusinessRuleException("Your application has been sent to admissions and can't be edited.");
        }

        $applicant = $application->applicant;
        $rules = ApplicantForm::formatRules($applicant->id);
        $fields = Arr::only($input, array_keys($rules));

        $valid = [];
        $skipped = [];
        foreach ($fields as $name => $value) {
            $value = is_string($value) ? trim($value) : $value;
            $value = $value === '' ? null : $value;
            $check = Validator::make(array_merge($fields, [$name => $value]), [$name => $rules[$name]], ApplicantForm::MESSAGES);
            if ($check->fails()) {
                $skipped[$name] = $check->errors()->first($name);
            } else {
                $valid[$name] = $value;
            }
        }

        $progress = $application->form_progress ?? ['step' => 1, 'completed' => []];
        $completed = array_values(array_unique(array_map('intval', array_intersect((array) ($input['completed'] ?? $progress['completed']), [1, 2, 3]))));
        sort($completed);
        $current = array_merge($this->formValues($application), $valid);
        $this->assertSectionsComplete(array_diff($completed, $progress['completed']), $current, $applicant->id);

        DB::transaction(function () use ($application, $applicant, $valid, $completed, $input, $progress, $by) {
            $this->saveProfile($application, $applicant, $valid, $by);
            $this->saveChoices($application, $valid);
            if (array_key_exists('campus', $valid)) {
                $application->campus = $valid['campus'];
            }
            $step = (int) ($input['step'] ?? $progress['step']);
            $application->form_progress = ['step' => max(1, min(3, $step)), 'completed' => $completed];
            $application->save();
        });

        return $skipped;
    }

    /** FR-03/FR-05: send the application to admissions. */
    public function submit(Application $application, User $by): void
    {
        if (! in_array($application->status, [ApplicationStatus::Draft, ApplicationStatus::Resubmit], true)) {
            throw new BusinessRuleException('This application has already been sent to admissions.');
        }

        $completed = $application->form_progress['completed'] ?? [];
        if (count(array_intersect([1, 2, 3], $completed)) < 3) {
            throw new BusinessRuleException('Finish all three sections of My application first.');
        }
        $this->assertSectionsComplete([1, 2, 3], $this->formValues($application), $application->applicant_id);

        $missing = $this->missingDocuments($application);
        if ($missing) {
            throw new BusinessRuleException('Upload these documents first: '.implode(', ', $missing).'.');
        }

        $resubmission = $application->status === ApplicationStatus::Resubmit;
        DB::transaction(function () use ($application, $by, $resubmission) {
            $this->status->transition($application, ApplicationStatus::Submitted, $by);
            $count = $application->documents()->whereHas('requirement', fn ($q) => $q->where('stage', Requirement::STAGE_ADMISSION))->count();
            $this->audit->record(
                $resubmission ? 'application.resubmitted' : 'application.submitted',
                $resubmission ? 'Resubmitted the requested documents' : "Submitted the application form and {$count} documents",
                $application, $application,
            );
        });
    }

    /** Admission requirements that apply to this applicant and are active (admin-store.js requiredDocs). */
    public function requiredDocuments(Application $application)
    {
        $applicant = $application->applicant;

        return Requirement::admission()
            ->where('is_active', true)
            ->where('is_uploadable', true)
            ->get()
            ->filter(fn (Requirement $r) => $r->appliesTo($applicant))
            ->values();
    }

    /** Titles of required documents not uploaded yet, or waiting for a replacement. */
    public function missingDocuments(Application $application): array
    {
        $docs = $application->documents()->get()->keyBy('requirement_id');

        return $this->requiredDocuments($application)
            ->filter(fn (Requirement $r) => ! $docs->has($r->id) || $docs[$r->id]->status === 'resubmit')
            ->pluck('title')
            ->all();
    }

    /** Draft, or sent back for resubmission (the applicant fixes and resubmits). */
    public function isEditable(Application $application): bool
    {
        return in_array($application->status, [ApplicationStatus::Draft, ApplicationStatus::Resubmit], true);
    }

    /** The form's current values, keyed by form field name. */
    public function formValues(Application $application): array
    {
        $applicant = $application->applicant;
        $values = [];
        foreach (ApplicantForm::PROFILE_FIELDS as $field => $column) {
            $value = $applicant->{$column};
            $values[$field] = match (true) {
                $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
                $value === null => null,
                default => (string) $value,
            };
        }
        $choices = $application->choices()->with('program')->get()->keyBy('rank');
        foreach ([1, 2, 3] as $rank) {
            $values['program'.$rank] = $choices[$rank]->program->name ?? null;
        }
        $values['campus'] = $application->campus;
        $values['email'] = $applicant->user->email;

        return $values;
    }

    private function assertSectionsComplete(array $sections, array $values, int $applicantId): void
    {
        $rules = ApplicantForm::formatRules($applicantId);
        $check = [];
        foreach ($sections as $section) {
            foreach (ApplicantForm::SECTION_REQUIRED[$section] ?? [] as $field) {
                $check[$field] = array_merge(['required'], array_diff($rules[$field], ['nullable']));
            }
        }
        if ($check) {
            Validator::make($values, $check, ApplicantForm::MESSAGES)->validate();
        }
    }

    private function saveProfile(Application $application, Applicant $applicant, array $valid, User $by): void
    {
        foreach (ApplicantForm::PROFILE_FIELDS as $field => $column) {
            if (array_key_exists($field, $valid)) {
                $applicant->{$column} = $valid[$field];
            }
        }
        if (array_key_exists('gradYear', $valid)) {
            $applicant->applicant_type = ApplicantForm::applicantType($valid['gradYear']);
        }
        if (array_key_exists('city', $valid)) {
            // Decision T7: the form has no residency field yet
            $applicant->is_resident = $valid['city'] ? (bool) preg_match('/\bpasig\b/i', $valid['city']) : null;
        }

        // FR-10: after the first submission, every profile change is kept
        if ($application->status !== ApplicationStatus::Draft) {
            foreach ($applicant->getDirty() as $column => $new) {
                ApplicantChange::create([
                    'applicant_id' => $applicant->id,
                    'field' => $column,
                    'old_value' => $applicant->getOriginal($column) instanceof \DateTimeInterface
                        ? $applicant->getOriginal($column)->format('Y-m-d') : $applicant->getOriginal($column),
                    'new_value' => $new,
                    'changed_by' => $by->id,
                ]);
            }
        }
        $applicant->save();
    }

    private function saveChoices(Application $application, array $valid): void
    {
        foreach ([1, 2, 3] as $rank) {
            if (! array_key_exists('program'.$rank, $valid)) {
                continue;
            }
            $application->choices()->where('rank', $rank)->delete();
            if ($valid['program'.$rank]) {
                $program = Program::where('name', $valid['program'.$rank])->firstOrFail();
                // A program can only hold one rank: drop it from the other rank first
                $application->choices()->where('program_id', $program->id)->delete();
                $application->choices()->create(['program_id' => $program->id, 'rank' => $rank]);
            }
        }
    }
}
