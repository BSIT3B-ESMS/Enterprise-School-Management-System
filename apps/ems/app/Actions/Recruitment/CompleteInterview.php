<?php

namespace App\Actions\Recruitment;

use App\Enums\AuditAction;
use App\Enums\InterviewStatus;
use App\Models\Interview;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class CompleteInterview
{
    public function __construct(private AuditLogger $audit, private ApplicationTimeline $timeline) {}

    /**
     * Marks a demo interview as done once it has started. After that HR decides: reject or issue an offer.
     *
     * @throws RecruitmentException
     */
    public function handle(User $actor, Interview $interview, ?string $notes = null): Interview
    {
        if ($interview->status !== InterviewStatus::Scheduled) {
            throw new RecruitmentException("A {$interview->status->value} interview cannot be completed.");
        }

        if ($interview->starts_at->isFuture()) {
            throw new RecruitmentException('The interview has not started yet.');
        }

        return DB::transaction(function () use ($actor, $interview, $notes): Interview {
            $interview->forceFill(['status' => InterviewStatus::Completed, 'completed_at' => now(), 'notes' => $notes ?: $interview->notes])->save();

            $application = $interview->application;
            $this->timeline->add($application, 'demo_completed', $actor);
            $this->audit->log(AuditAction::Update, $interview, ['event' => 'interview_completed'], $actor);

            return $interview;
        });
    }
}
