<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\Role;
use App\Exceptions\BusinessRuleException;
use App\Models\AdmissionPeriod;
use App\Models\Application;
use App\Models\InterviewAssignment;
use App\Models\InterviewSchedule;
use App\Models\Program;
use App\Models\User;
use App\Notifications\AdmissionNotice;
use App\Services\Dms\DmsSync;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Interviews (FR-17). Admission personnel create schedules per program and
 * assign applicants who passed the exam to the schedule of their first
 * choice that requires an interview; the next choice is scheduled only after
 * the earlier dean rejects them. The College Dean of each program's
 * college accepts (passed) or rejects (failed) the applicant, with an
 * optional grade (Restricted). Applicants see their schedules and outcomes,
 * never the grade.
 */
class InterviewService
{
    public function __construct(
        private ApplicationStatusService $status,
        private OfferService $offers,
        private AuditLogger $audit,
        private DmsSync $dms,
    ) {}

    public function createSchedule(Program $program, string $date, ?string $time, string $venue, int $capacity, User $staff): InterviewSchedule
    {
        if (! $program->requires_interview) {
            throw new BusinessRuleException("{$program->code} doesn't require an interview.");
        }
        $schedule = InterviewSchedule::create([
            'admission_period_id' => AdmissionPeriod::current()->id,
            'program_id' => $program->id,
            'date' => $date, 'time' => $time, 'venue' => trim($venue), 'capacity' => $capacity, 'created_by' => $staff->id,
        ]);
        $this->audit->record('interview.schedule_created', "Created interview schedule {$schedule->label()} at {$schedule->venue} ({$capacity} seats)", $schedule);

        return $schedule;
    }

    /** Assign (or move) an applicant to an interview schedule of one of their choices. Capacity is enforced under a lock. */
    public function assign(Application $application, InterviewSchedule $schedule, User $staff): InterviewAssignment
    {
        if ($application->status !== ApplicationStatus::ForInterview) {
            throw new BusinessRuleException('Only applicants waiting for an interview can be scheduled.');
        }
        if (! $application->choices()->where('program_id', $schedule->program_id)->exists()) {
            throw new BusinessRuleException("{$schedule->program->code} is not one of this applicant's three choices.");
        }
        $pending = $this->offers->pendingInterview($application);
        if ($pending?->id !== $schedule->program_id && ! $this->assignmentFor($application, $schedule->program_id)) {
            throw new BusinessRuleException("Interview {$pending?->code} first. {$schedule->program->code} is scheduled only if its dean rejects the earlier choice.");
        }
        $existing = $this->assignmentFor($application, $schedule->program_id);
        if ($existing?->outcome) {
            throw new BusinessRuleException('The interview outcome is already recorded.');
        }

        $assignment = DB::transaction(function () use ($application, $schedule, $staff, $existing) {
            $locked = InterviewSchedule::lockForUpdate()->findOrFail($schedule->id);
            $booked = $locked->assignments()->where('application_id', '!=', $application->id)->count();
            if ($booked >= $locked->capacity) {
                throw new BusinessRuleException('That interview schedule is full. Pick another one.');
            }
            $assignment = $existing ?? new InterviewAssignment(['application_id' => $application->id]);
            $assignment->fill(['interview_schedule_id' => $schedule->id, 'assigned_by' => $staff->id])->save();
            $this->audit->record('interview.assigned', "Scheduled the interview · {$schedule->label()}", $assignment, $application);
            $this->dms->record('interview.scheduled', $application, ['program' => $schedule->program->code, 'date' => $schedule->date->toDateString(), 'time' => $schedule->time, 'venue' => $schedule->venue]);

            return $assignment;
        });

        $application->applicant->user->notify(new AdmissionNotice('interview_scheduled', 'Your interview schedule',
            "Your {$schedule->program->name} interview is on {$schedule->date->format('l, F j, Y')}".($schedule->time ? ", {$schedule->time}" : '')." at {$schedule->venue}.",
            $application->number));

        return $assignment;
    }

    /**
     * The dean accepts (passed) or rejects (failed) the applicant for the
     * dean's own program. Final once recorded. The offer follows the highest
     * accepting choice, once every higher choice is decided.
     */
    public function record(Application $application, Program $program, string $outcome, User $dean, ?int $grade = null, ?string $remark = null): InterviewAssignment
    {
        $assignment = $this->assignmentFor($application, $program->id)?->load('schedule.program')
            ?? throw new BusinessRuleException("This applicant has no {$program->code} interview scheduled.");
        $this->assertDeanOf($dean, $program);
        if ($assignment->outcome) {
            throw new BusinessRuleException('The interview outcome is already recorded.');
        }

        DB::transaction(function () use ($application, $assignment, $program, $grade, $outcome, $dean, $remark) {
            $assignment->update([
                'grade' => $grade, 'outcome' => $outcome, 'recorded_by' => $dean->id, 'recorded_at' => now(),
                'remark' => $remark ? mb_substr(trim($remark), 0, 500) : null,
            ]);
            $this->audit->record('interview.recorded', 'Recorded the evaluation: '.($outcome === InterviewAssignment::PASSED ? "Accepted into {$program->code}" : "Rejected from {$program->code}"), $assignment, $application);
            $this->dms->record('interview.recorded', $application, array_filter([
                'program' => $program->code,
                'outcome' => $outcome,
                'grade' => $grade === null ? null : ['value' => $grade, 'classification' => 'restricted'],
            ]));
            $this->offers->resolveChoices($application, $dean);
        });

        return $assignment->fresh();
    }

    private function assignmentFor(Application $application, int $programId): ?InterviewAssignment
    {
        return $application->interviews()->whereHas('schedule', fn ($q) => $q->where('program_id', $programId))->first();
    }

    /** Programs whose interviews the user may see: the dean's college, or all for admission personnel. */
    public function visiblePrograms(User $user)
    {
        if ($user->isAdmissionOffice() || $user->hasRole(Role::ReadOnly->value)) {
            return Program::orderBy('code')->get();
        }
        $college = $user->deanOf;

        return $college ? $college->programs()->orderBy('code')->get() : collect();
    }

    private function assertDeanOf(User $dean, Program $program): void
    {
        // The Admin runs the whole system, the College Dean module included
        if ($dean->hasAnyRole([Role::AdmissionHead->value, Role::SuperAdmin->value])) {
            return;
        }
        if (! $dean->hasRole(Role::CollegeDean->value) || ! $program->college_id || $dean->deanOf?->id !== $program->college_id) {
            throw new AuthorizationException("Only the dean of {$program->code}'s college can record this interview.");
        }
    }
}
