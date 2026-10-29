<?php

namespace App\Actions\Recruitment;

use App\Enums\ApplicationStatus;
use App\Enums\AuditAction;
use App\Enums\InterviewStatus;
use App\Enums\OfferStatus;
use App\Models\Application;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;

class RejectApplication
{
    public function __construct(private AuditLogger $audit, private ApplicationTimeline $timeline, private Notifier $notifier) {}

    /**
     * HR rejects an application that is Applied, in Demo Interview, or Offered (which withdraws the offer). The reason is
     * kept and the applicant is told. A scheduled interview is cancelled with it.
     *
     * @throws RecruitmentException
     */
    public function handle(User $actor, Application $application, string $reason): Application
    {
        if ($application->status->isFinal()) {
            throw new RecruitmentException("An application that is {$application->status->label()} cannot be rejected.");
        }

        if (blank($reason)) {
            throw new RecruitmentException('Give a reason for rejecting the application.');
        }

        return DB::transaction(function () use ($actor, $application, $reason): Application {
            $application->loadMissing('applicant.applicantProfile', 'jobTitle.college');
            $interview = $application->interviews()->scheduled()->first();

            $interview?->forceFill(['status' => InterviewStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => 'The application was rejected.'])->save();
            $application->offers()->pending()->update(['status' => OfferStatus::Withdrawn, 'responded_at' => now()]);

            $application->forceFill(['status' => ApplicationStatus::Rejected, 'status_reason' => $reason, 'status_changed_at' => now()])->save();

            $this->timeline->add($application, 'rejected', $actor, ['reason' => $reason]);
            $this->audit->log(AuditAction::Update, $application, ['event' => 'application_rejected', 'reason' => $reason], $actor);

            $this->notifier->notify($application->applicant, 'Application update', "Your application for {$application->jobTitle->title} was not successful. {$reason}", route('applicant.applications.show', $application));

            if ($interview) {
                $this->notifier->notify(
                    collect([$interview->interviewer])->merge($this->notifier->collegeLeaders($application->jobTitle->college)),
                    'Demo interview cancelled',
                    "{$application->applicant->applicantProfile->full_name}'s application for {$application->jobTitle->title} was rejected; the demo interview will not happen.",
                    route('recruitment.show', $application),
                );
            }

            return $application;
        });
    }
}
