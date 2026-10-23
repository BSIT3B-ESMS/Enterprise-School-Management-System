<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\AdmissionPeriod;
use App\Models\Program;
use App\Models\Strand;
use App\Models\ExamSession;
use App\Models\Barangay;
use App\Models\Requirement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Programs, slots, requirements and the admission period (FR-09). Every
 * change is written to the audit log, worded like the panel's own log.
 */
class AdmissionSetupService
{
    public function __construct(private AuditLogger $audit) {}

    public function setSlots(Program $program, int $slots): Program
    {
        return DB::transaction(function () use ($program, $slots) {
            $program = Program::lockForUpdate()->findOrFail($program->id);
            if ($program->slots === $slots) {
                return $program;
            }
            $filled = $program->filledSlots();
            if ($slots < $filled) {
                throw new BusinessRuleException("{$program->code} already has {$filled} accepted slots. Slots can't go below that.");
            }

            $this->audit->record('program.slots', "Changed {$program->code} slots from {$program->slots} to {$slots}", $program, meta: ['from' => $program->slots, 'to' => $slots]);
            $program->update(['slots' => $slots]);

            return $program;
        });
    }

    public function addProgram(string $code, string $name, int $slots): Program
    {
        $code = strtoupper(trim($code));
        if (Program::withTrashed()->where('code', $code)->exists()) {
            throw new BusinessRuleException("There is already a program with the code {$code}.");
        }

        $program = Program::create(['code' => $code, 'name' => trim($name), 'slots' => $slots]);
        $this->audit->record('program.added', "Added program {$code} · {$program->name} ({$slots} slots)", $program);

        return $program;
    }

    public function setRequirementActive(Requirement $requirement, bool $active): void
    {
        if ($requirement->is_active === $active) {
            return;
        }
        $requirement->update(['is_active' => $active]);
        $this->audit->record('requirement.toggled', ($active ? 'Required' : 'Stopped requiring')." \"{$requirement->title}\"", $requirement);
    }

    /** FR-26: open or close a program for new applications, or rename it. */
    public function updateProgram(Program $program, array $changes): Program
    {
        $before = ['name' => $program->name, 'is_open' => $program->is_open];
        $program->fill(array_intersect_key($changes, $before));
        if (! $program->isDirty()) {
            return $program;
        }
        $after = ['name' => $program->name, 'is_open' => $program->is_open];
        $program->save();
        $text = $program->wasChanged('is_open') && ! $program->wasChanged('name')
            ? ($program->is_open ? 'Opened' : 'Closed')." {$program->code} for applications"
            : "Updated program {$program->code}";
        $this->audit->record('program.updated', $text, $program, meta: ['before' => $before, 'after' => $after]);

        return $program;
    }

    /**
     * FR-26: admission period dates and the acceptance window, without code
     * changes. Keys are admission_periods columns.
     */
    public function updatePeriod(AdmissionPeriod $period, array $changes): AdmissionPeriod
    {
        $fields = ['label', 'opens_on', 'application_deadline', 'exam_starts_on', 'exam_ends_on', 'results_release_on', 'acceptance_window_days'];
        $changes = array_intersect_key($changes, array_flip($fields));
        $before = collect($fields)->mapWithKeys(fn ($f) => [$f => $period->{$f} instanceof \DateTimeInterface ? $period->{$f}->format('Y-m-d') : $period->{$f}])->all();
        $period->fill($changes);
        if (! $period->isDirty()) {
            return $period;
        }
        $period->save();
        $after = collect($fields)->mapWithKeys(fn ($f) => [$f => $period->{$f} instanceof \DateTimeInterface ? $period->{$f}->format('Y-m-d') : $period->{$f}])->all();
        $changed = array_keys(array_diff_assoc(array_map('strval', $after), array_map('strval', $before)));
        $this->audit->record('period.updated', 'Changed the admission period: '.implode(', ', str_replace('_', ' ', $changed)), $period,
            meta: ['before' => array_intersect_key($before, array_flip($changed)), 'after' => array_intersect_key($after, array_flip($changed))]);

        return $period;
    }

    /** FR-06, FR-26: add or change a strand and the programs suggested for it. */
    public function saveStrand(string $name, array $programCodes, bool $active = true): Strand
    {
        $name = trim($name);
        $strand = Strand::firstOrNew(['name' => $name]);
        $before = $strand->exists ? ['active' => $strand->is_active, 'programs' => $strand->programs()->orderBy('code')->pluck('code')->all()] : null;
        $programs = Program::whereIn('code', $programCodes)->orderBy('code')->get(['id', 'code']);
        if ($programs->count() !== count(array_unique($programCodes))) {
            throw new BusinessRuleException('Unknown program code: '.implode(', ', array_diff($programCodes, $programs->pluck('code')->all())).'.');
        }
        DB::transaction(function () use ($strand, $programs, $active) {
            $strand->sort_order ??= (int) Strand::max('sort_order') + 1;
            $strand->is_active = $active;
            $strand->save();
            $strand->programs()->sync($programs->pluck('id'));
        });
        $after = ['active' => $active, 'programs' => $programs->pluck('code')->all()];
        if ($before !== $after) {
            $this->audit->record('strand.saved', ($before ? 'Updated' : 'Added')." strand {$name}: suggests ".($after['programs'] ? implode(', ', $after['programs']) : 'no programs').($active ? '' : ' (hidden from the form)'), $strand,
                meta: ['before' => $before, 'after' => $after]);
        }

        return $strand;
    }

    /** FR-26: the barangays of one district. Names left out are hidden from the form, not deleted. */
    public function saveBarangays(int $district, array $names): array
    {
        $names = array_values(array_unique(array_filter(array_map('trim', $names))));
        $before = Barangay::where('district', $district)->where('is_active', true)->orderBy('name')->pluck('name')->all();
        DB::transaction(function () use ($district, $names) {
            Barangay::where('district', $district)->whereNotIn('name', $names)->update(['is_active' => false]);
            foreach ($names as $name) {
                Barangay::updateOrCreate(['district' => $district, 'name' => $name], ['is_active' => true]);
            }
        });
        $after = Barangay::where('district', $district)->where('is_active', true)->orderBy('name')->pluck('name')->all();
        if ($before !== $after) {
            $added = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));
            $this->audit->record('barangays.saved', "Updated the District {$district} barangays".($added ? ' · added '.implode(', ', $added) : '').($removed ? ' · removed '.implode(', ', $removed) : ''),
                meta: ['district' => $district, 'added' => $added, 'removed' => $removed]);
        }

        return $after;
    }

    /** FR-26: change an exam session's venue or capacity (never below the seats taken). */
    public function updateSession(ExamSession $session, array $changes): ExamSession
    {
        return DB::transaction(function () use ($session, $changes) {
            $session = ExamSession::lockForUpdate()->findOrFail($session->id);
            $taken = $session->registrations()->count();
            if (isset($changes['capacity']) && $changes['capacity'] < $taken) {
                throw new BusinessRuleException("{$taken} examinees are already assigned to this session. Capacity can't go below that.");
            }
            $before = ['venue' => $session->venue, 'capacity' => $session->capacity];
            $session->fill(array_intersect_key($changes, $before));
            if ($session->isDirty()) {
                $session->save();
                $this->audit->record('exam.session_updated', "Updated exam session {$session->label()}: {$session->venue}, {$session->capacity} seats", $session,
                    meta: ['before' => $before, 'after' => ['venue' => $session->venue, 'capacity' => $session->capacity]]);
            }

            return $session;
        });
    }

    /** FR-26: remove a session nobody is assigned to. */
    public function deleteSession(ExamSession $session): void
    {
        if ($session->registrations()->exists()) {
            throw new BusinessRuleException('Examinees are assigned to this session. Move them first.');
        }
        $this->audit->record('exam.session_deleted', "Removed exam session {$session->label()} at {$session->venue}", $session);
        $session->delete();
    }

    public function setDeadline(AdmissionPeriod $period, string $date): void
    {
        $deadline = Carbon::parse($date);
        if ($period->application_deadline->isSameDay($deadline)) {
            return;
        }
        $period->update(['application_deadline' => $deadline]);
        $this->audit->record('period.deadline', 'Changed the application deadline to '.$deadline->format('F j, Y'), $period);
    }
}
