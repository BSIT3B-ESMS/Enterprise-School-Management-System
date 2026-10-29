<?php

namespace App\Actions\Recruitment;

use App\Enums\ApplicationStatus;
use App\Enums\AuditAction;
use App\Models\Application;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;

/**
 * Puts a rejected application back in the Applied stage so HR can review it again and schedule a new demo interview.
 */
class ReopenApplication
{
    public function __construct(
        private AuditLogger $audit,
        private ApplicationTimeline $timeline,
        private Notifier $notifier,
    ) {}

    /**
     * @throws RecruitmentException
     */
    public function handle(User $actor, Application $application): Application
    {
        if ($application->status !== ApplicationStatus::Rejected) {
            throw new RecruitmentException("An application that is {$application->status->label()} cannot be reopened.");
        }

        return DB::transaction(function () use ($actor, $application): Application {
            $application->forceFill([
                'status' => ApplicationStatus::Applied,
                'status_reason' => null,
                'status_changed_at' => now(),
            ])->save();

            $this->timeline->add($application, 'reopened', $actor);
            $this->audit->log(AuditAction::Update, $application, ['event' => 'application_reopened'], $actor);

            $application->loadMissing('jobTitle');

            $this->notifier->notify(
                $application->applicant,
                'Application reopened',
                "Your application for {$application->jobTitle->title} is being reviewed again.",
                route('applicant.applications.show', $application),
            );

            return $application;
        });
    }
}
