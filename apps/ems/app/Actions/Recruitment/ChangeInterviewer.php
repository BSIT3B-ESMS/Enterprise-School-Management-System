<?php

namespace App\Actions\Recruitment;

use App\Enums\AuditAction;
use App\Enums\InterviewStatus;
use App\Enums\Role;
use App\Models\Interview;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use App\Services\InterviewConflicts;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;

class ChangeInterviewer
{
    public function __construct(
        private AuditLogger $audit,
        private ApplicationTimeline $timeline,
        private InterviewConflicts $conflicts,
        private Notifier $notifier,
    ) {}

    /**
     * Hands a demo interview to another HR Staff or HR Admin. Both the old and the new interviewer are notified. If the new
     * interviewer is busy at that time, the change stops with the conflicts.
     *
     * @throws RecruitmentException
     * @throws InterviewConflictException
     */
    public function handle(User $actor, Interview $interview, User $newInterviewer): Interview
    {
        if ($interview->status !== InterviewStatus::Scheduled) {
            throw new RecruitmentException("A {$interview->status->value} interview cannot change interviewer.");
        }

        if ($newInterviewer->id === $interview->interviewer_id) {
            throw new RecruitmentException('That person is already the interviewer.');
        }

        if (! in_array($newInterviewer->role, [Role::HrAdmin, Role::HrStaff], true) || ! $newInterviewer->is_active) {
            throw new RecruitmentException('The interviewer must be an active HR Staff or HR Admin.');
        }

        $application = $interview->application()->with('applicant.applicantProfile', 'jobTitle')->firstOrFail();
        $found = $this->conflicts->check($newInterviewer, $application, $interview->starts_at, $interview->ends_at, $interview);

        if ($found !== []) {
            throw new InterviewConflictException($found, $this->conflicts->freeInterviewers($application, $interview->starts_at, $interview->ends_at, $interview));
        }

        return DB::transaction(function () use ($actor, $interview, $application, $newInterviewer): Interview {
            $previous = $interview->interviewer;

            $interview->forceFill(['interviewer_id' => $newInterviewer->id])->save();

            $this->timeline->add($application, 'interviewer_changed', $actor, ['from' => $previous->name, 'to' => $newInterviewer->name]);
            $this->audit->log(AuditAction::Update, $interview, ['event' => 'interviewer_changed', 'from' => $previous->name, 'to' => $newInterviewer->name], $actor);

            $name = $application->applicant->applicantProfile->full_name;
            $when = $interview->starts_at->format('M j, Y g:i A');

            $this->notifier->notify($previous, 'You are no longer the interviewer', "{$name} for {$application->jobTitle->title} on {$when} is now handled by {$newInterviewer->name}.", route('recruitment.show', $application));
            $this->notifier->notify($newInterviewer, 'You are assigned to a demo interview', "{$name} for {$application->jobTitle->title}, {$when}.", route('recruitment.show', $application));

            return $interview;
        });
    }
}
