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
 * assign applicants who passed the exam and whose program requires an
 * interview. The College Dean of that program's college records the grade
 * (Restricted) and Passed/Failed. Passed → offer; Failed → final.
 * Applicants see their schedule and outcome, never the grade.
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

    /** Assign (or move) an applicant to an interview schedule. Capacity is enforced under a lock. */
    public function assign(Application $application, InterviewSchedule $schedule, User $staff): InterviewAssignment
    {
        if ($application->status !== ApplicationStatus::ForInterview) {
            throw new BusinessRuleException('Only applicants waiting for an interview can be scheduled.');
        }
        if ($application->interview?->outcome) {
            throw new BusinessRuleException('The interview outcome is already recorded.');
        }
        if ($application->firstChoice()?->id !== $schedule->program_id) {
            throw new BusinessRuleException("This schedule is for {$schedule->program->code}, not the applicant's first choice.");
        }

        $assignment = DB::transaction(function () use ($application, $schedule, $staff) {
            $locked = InterviewSchedule::lockForUpdate()->findOrFail($schedule->id);
            $booked = $locked->assignments()->where('application_id', '!=', $application->id)->count();
            if ($booked >= $locked->capacity) {
                throw new BusinessRuleException('That interview schedule is full. Pick another one.');
            }
            $assignment = InterviewAssignment::updateOrCreate(['application_id' => $application->id], [
                'interview_schedule_id' => $schedule->id, 'assigned_by' => $staff->id,
            ]);
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
     * The dean records the grade and outcome (own college only). Final once
     * recorded. Passed → offer; Failed → final; Referred → offer for another
     * of the applicant's choices (one without its own interview, with slots).
     */
    public function record(Application $application, int $grade, string $outcome, User $dean, ?Program $referTo = null, ?string $remark = null): InterviewAssignment
    {
        $assignment = $application->interview?->load('schedule.program')
            ?? throw new BusinessRuleException('This applicant has no interview scheduled.');
        $this->assertDeanOf($dean, $assignment->schedule->program);
        if ($assignment->outcome) {
            throw new BusinessRuleException('The interview outcome is already recorded.');
        }
        if ($outcome === InterviewAssignment::REFERRED) {
            $this->assertReferral($application, $assignment->schedule->program, $referTo);
        }

        DB::transaction(function () use ($application, $assignment, $grade, $outcome, $dean, $referTo, $remark) {
            $assignment->update([
                'grade' => $grade, 'outcome' => $outcome, 'recorded_by' => $dean->id, 'recorded_at' => now(),
                'referred_program_id' => $outcome === InterviewAssignment::REFERRED ? $referTo->id : null,
                'remark' => $remark ? mb_substr(trim($remark), 0, 500) : null,
            ]);
            $label = match ($outcome) {
                InterviewAssignment::PASSED => 'Passed',
                InterviewAssignment::REFERRED => "Referred to {$referTo->code}",
                default => 'Failed',
            };
            $this->audit->record('interview.recorded', "Recorded the evaluation: {$label}", $assignment, $application);
            $this->dms->record('interview.recorded', $application, [
                'program' => $assignment->schedule->program->code,
                'outcome' => $outcome,
                'grade' => ['value' => $grade, 'classification' => 'restricted'],
            ]);

            match ($outcome) {
                InterviewAssignment::PASSED => $this->offers->offer($application, $dean, $assignment->schedule->program),
                InterviewAssignment::REFERRED => $this->offers->offer($application, $dean, $referTo),
                default => $this->status->transition($application, ApplicationStatus::InterviewFailed, $dean),
            };
        });

        return $assignment->fresh();
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

    private function assertReferral(Application $application, Program $evaluated, ?Program $referTo): void
    {
        if (! $referTo || $referTo->id === $evaluated->id) {
            throw new BusinessRuleException('Pick the program to refer the applicant to.');
        }
        if (! $application->choices()->where('program_id', $referTo->id)->exists()) {
            throw new BusinessRuleException("{$referTo->code} is not one of this applicant's three choices.");
        }
        if ($referTo->requires_interview) {
            throw new BusinessRuleException("{$referTo->code} needs its own interview. Refer to a program without one.");
        }
        if ((Program::filledCounts()[$referTo->id] ?? 0) >= $referTo->slots) {
            throw new BusinessRuleException("All {$referTo->code} slots have been taken.");
        }
    }

    private function assertDeanOf(User $dean, Program $program): void
    {
        // The Admin runs the whole system, the College Dean module included
        if ($dean->hasRole(Role::AdmissionHead->value)) {
            return;
        }
        if (! $dean->hasRole(Role::CollegeDean->value) || ! $program->college_id || $dean->deanOf?->id !== $program->college_id) {
            throw new AuthorizationException("Only the dean of {$program->code}'s college can record this interview.");
        }
    }
}
