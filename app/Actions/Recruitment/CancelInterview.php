<?php

namespace App\Actions\Recruitment;

use App\Enums\ApplicationStatus;
use App\Enums\AuditAction;
use App\Enums\InterviewStatus;
use App\Models\Interview;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;

class CancelInterview
{
    public function __construct(private AuditLogger $audit, private ApplicationTimeline $timeline, private Notifier $notifier) {}

    /**
     * HR cancels a demo interview that has not happened. The application goes back to Applied so a new one can be set. The
     * applicant, the interviewer (when someone else cancelled), and the college's leaders are told.
     *
     * @throws RecruitmentException
     */
    public function handle(User $actor, Interview $interview, string $reason): Interview
    {
        if ($interview->status !== InterviewStatus::Scheduled) {
            throw new RecruitmentException("A {$interview->status->value} interview cannot be cancelled.");
        }

        if (blank($reason)) {
            throw new RecruitmentException('Give a reason for cancelling the interview.');
        }

        return DB::transaction(function () use ($actor, $interview, $reason): Interview {
            $application = $interview->application()->with('applicant.applicantProfile', 'jobTitle.college')->firstOrFail();

            $interview->forceFill(['status' => InterviewStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => $reason])->save();
            $application->forceFill(['status' => ApplicationStatus::Applied, 'status_changed_at' => now()])->save();

            $this->timeline->add($application, 'demo_cancelled', $actor, ['reason' => $reason, 'starts_at' => $interview->starts_at->toDateTimeString()]);
            $this->audit->log(AuditAction::Update, $interview, ['event' => 'interview_cancelled', 'reason' => $reason], $actor);

            $name = $application->applicant->applicantProfile->full_name;
            $when = $interview->starts_at->format('M j, Y g:i A');

            $this->notifier->notify($application->applicant, 'Demo interview cancelled', "Your demo interview for {$application->jobTitle->title} on {$when} was cancelled. Reason: {$reason} HR will schedule a new one.", route('applicant.applications.show', $application));

            if ($interview->interviewer_id !== $actor->id) {
                $this->notifier->notify($interview->interviewer, 'Demo interview cancelled', "{$name} for {$application->jobTitle->title} on {$when}. Reason: {$reason}", route('recruitment.show', $application));
            }

            $this->notifier->notify($this->notifier->collegeLeaders($application->jobTitle->college), 'Demo interview cancelled', "{$name} for {$application->jobTitle->title} on {$when} was cancelled.", route('recruitment.show', $application));

            return $interview;
        });
    }
}
