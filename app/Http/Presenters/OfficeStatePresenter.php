<?php

namespace App\Http\Presenters;

use App\Models\AdmissionPeriod;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\ExamSession;
use App\Models\Program;
use App\Models\Requirement;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Builds the admission office panel's state (ADM.state in admin-store.js):
 * { programs, sessions, applicants, settings, audit, staff }. Every screen,
 * chart, report and CSV export in the panel is derived from it.
 *
 * The internal score is included for admission personnel only (NFR-02).
 */
class OfficeStatePresenter
{
    private const RELATIONS = [
        'applicant.user', 'choices.program', 'documents.requirement', 'documents.reviewer',
        'examRegistration', 'result.recorder', 'auditLogs',
    ];

    public function state(User $staff): array
    {
        $period = AdmissionPeriod::current();
        $applications = Application::where('admission_period_id', $period->id)
            ->with(self::RELATIONS)
            ->orderBy('id')
            ->get();

        return [
            'version' => 2,
            'staff' => [
                'name' => $staff->name,
                'initials' => $staff->initials(),
                'role' => $staff->primaryRole()?->label(),
                'roleKey' => $staff->primaryRole()?->value,
                'email' => $staff->email,
            ],
            'programs' => Program::orderBy('id')->get()->map(fn (Program $p) => [
                'code' => $p->code, 'name' => $p->name, 'slots' => $p->slots,
            ])->all(),
            'sessions' => ExamSession::where('admission_period_id', $period->id)->orderBy('date')->orderBy('batch')->get()
                ->map(fn (ExamSession $s) => $this->session($s))->all(),
            'applicants' => $applications->map(fn (Application $a) => $this->present($a, $staff))->all(),
            'settings' => [
                'deadline' => $period->application_deadline->toDateString(),
                'period' => $period->label,
                'inactiveDocs' => Requirement::admission()->where('is_active', false)->pluck('key')->all(),
            ],
            'audit' => AuditLog::whereNull('application_id')->orderBy('id')->get()
                ->map(fn (AuditLog $l) => $this->entry($l))->all(),
        ];
    }

    /** One applicant, after an action (the panel replaces its copy). */
    public function applicant(Application $application, ?User $staff = null): array
    {
        return $this->present($application->load(self::RELATIONS), $staff ?? request()->user());
    }

    public function session(ExamSession $s): array
    {
        return [
            'id' => $s->publicId(),
            'date' => $s->date->toDateString(),
            'batch' => $s->batch,
            'venue' => $s->venue,
            'capacity' => $s->capacity,
        ];
    }

    private function present(Application $application, User $staff): array
    {
        $p = $application->applicant;
        $docs = $application->documents->groupBy(fn (ApplicationDocument $d) => $d->requirement->stage);
        $reg = $application->examRegistration;
        $result = $application->result;

        $out = [
            'id' => $application->number,
            'lrn' => $p->lrn,
            'lastName' => $p->last_name,
            'firstName' => $p->first_name,
            'middleName' => $p->middle_name,
            'sex' => $p->sex,
            'email' => $p->user->email,
            'phone' => $p->phone,
            'resident' => (bool) $p->is_resident,
            'district' => $p->district,
            'barangay' => $p->barangay ?? '',
            'address' => implode(', ', array_filter([$p->address, $p->city, $p->province])),
            'birthday' => $p->birthday?->toDateString(),
            'schoolType' => $p->school_type,
            'lastSchool' => $p->last_school,
            'strand' => $p->strand,
            'applicantType' => $p->applicant_type ?? 'grade12',
            'hasGuardian' => (bool) $p->has_guardian,
            'programs' => $application->choices->map(fn ($c) => $c->program->code)->values()->all(),
            'createdAt' => $this->at($application->created_at),
            'submittedAt' => $application->submitted_at ? $this->at($application->submitted_at) : null,
            'docs' => $this->files($docs->get(Requirement::STAGE_ADMISSION, collect())),
            'exam' => $reg ? [
                'sessionId' => 'S'.$reg->exam_session_id,
                'permit' => $reg->permit_no,
                'attendance' => $reg->attendance,
                'permitChecked' => $reg->permit_checked,
                'formChecked' => $reg->form_checked,
            ] : null,
            'result' => $result ? array_filter([
                'score' => $staff->hasRole('admission') ? $result->score : null,
                'decision' => $result->decision,
                'recordedBy' => $result->recorder->name,
                'recordedAt' => $this->at($result->recorded_at),
                'released' => $result->is_released,
                'releasedAt' => $result->released_at ? $this->at($result->released_at) : null,
            ], fn ($v) => $v !== null) : null,
            'history' => $application->auditLogs->map(fn (AuditLog $l) => $this->entry($l))->all(),
        ];

        if ($application->medicalApplies()) {
            $out['medical'] = ['files' => $this->files($docs->get(Requirement::STAGE_MEDICAL, collect()))];
        }

        return $out;
    }

    private function files(Collection $documents): array
    {
        return $documents->mapWithKeys(fn (ApplicationDocument $d) => [$d->requirement->key => array_filter([
            'status' => $d->status,
            'file' => $d->original_name,
            'size' => $d->size,
            'uploadedAt' => $this->at($d->created_at),
            'reviewedBy' => $d->reviewer?->name,
            'reviewedAt' => $d->reviewed_at ? $this->at($d->reviewed_at) : null,
            'remark' => $d->remark,
        ], fn ($v) => $v !== null)])->all();
    }

    private function entry(AuditLog $log): array
    {
        return ['at' => $this->at($log->created_at), 'by' => $log->actor_name, 'text' => $log->description];
    }

    /** "2026-10-17T09:05", the timestamp format the panel uses. */
    private function at(CarbonInterface $time): string
    {
        return $time->copy()->setTimezone(config('admission.display_timezone'))->format('Y-m-d\TH:i');
    }
}
