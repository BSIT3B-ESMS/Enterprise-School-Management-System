<?php

namespace App\Actions\Recruitment;

use App\Enums\ApplicationStatus;
use App\Enums\AuditAction;
use App\Enums\InterviewStatus;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;

class CancelApplication
{
    public function __construct(private AuditLogger $audit, private ApplicationTimeline $timeline, private Notifier $notifier) {}

    /**
     * The applicant cancels their own application. It is flagged Cancelled and any scheduled interview is cancelled with it.
     * Within 24 hours of the demo interview this is refused. An offer is answered with the offer's own accept or decline.
     *
     * @throws RecruitmentException
     */
    public function handle(User $applicant, Application $application, ?string $reason = null): Application
    {
        if ($application->user_id !== $applicant->id) {
            throw new RecruitmentException('This application is not yours.');
        }

        if (! in_array($application->status, [ApplicationStatus::Applied, ApplicationStatus::DemoInterview], true)) {
            throw new RecruitmentException($application->status === ApplicationStatus::Offered
                ? 'Answer the offer instead: accept it or decline it.'
                : "An application that is {$application->status->label()} cannot be cancelled.");
        }

        $interview = $application->interviews()->scheduled()->first();

        if ($interview && ! $interview->canBeCancelledByApplicant()) {
            throw new RecruitmentException('Your demo interview is less than '.Interview::CANCEL_CUTOFF_HOURS.' hours away, so the application can no longer be cancelled. Please attend, or contact HR.');
        }

        return DB::transaction(function () use ($applicant, $application, $interview, $reason): Application {
            $application->loadMissing('jobTitle.college');

            if ($interview) {
                $interview->forceFill(['status' => InterviewStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => 'The applicant cancelled the application.'])->save();
            }

            $application->forceFill(['status' => ApplicationStatus::Cancelled, 'status_reason' => $reason, 'status_changed_at' => now()])->save();

            $this->timeline->add($application, 'cancelled', $applicant, array_filter(['reason' => $reason]));
            $this->audit->log(AuditAction::Update, $application, ['event' => 'application_cancelled'], $applicant);

            $name = $applicant->applicantProfile->full_name;

            $this->notifier->notify(
                collect([$interview?->interviewer, $interview?->scheduler])->merge($this->notifier->hrAdmins()),
                'Application cancelled',
                "{$name} cancelled the application for {$application->jobTitle->title}.",
                route('recruitment.show', $application),
            );

            if ($interview) {
                $this->notifier->notify($this->notifier->collegeLeaders($application->jobTitle->college), 'Demo interview cancelled', "{$name} cancelled the application for {$application->jobTitle->title}; the demo interview on {$interview->starts_at->format('M j, Y g:i A')} will not happen.", route('recruitment.show', $application));
            }

            return $application;
        });
    }
}
