<?php

namespace App\Actions\Recruitment;

use App\Enums\AuditAction;
use App\Enums\InterviewStatus;
use App\Models\Interview;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;

class RequestInterviewReschedule
{
    public function __construct(private AuditLogger $audit, private ApplicationTimeline $timeline, private Notifier $notifier) {}

    /**
     * The applicant asks HR to move the demo interview, giving the reason and the days they are free. The interviewer and
     * whoever scheduled it are notified; HR then picks the new time.
     *
     * @throws RecruitmentException
     */
    public function handle(User $applicant, Interview $interview, string $reason, string $availableDays): Interview
    {
        $application = $interview->application()->with('jobTitle')->firstOrFail();

        if ($application->user_id !== $applicant->id) {
            throw new RecruitmentException('This interview is not yours.');
        }

        if ($interview->status !== InterviewStatus::Scheduled) {
            throw new RecruitmentException('This interview can no longer be rescheduled.');
        }

        if (blank($reason) || blank($availableDays)) {
            throw new RecruitmentException('Say why you cannot attend and which days you are available.');
        }

        return DB::transaction(function () use ($applicant, $interview, $application, $reason, $availableDays): Interview {
            $interview->forceFill([
                'reschedule_requested_at' => now(),
                'reschedule_reason' => $reason,
                'reschedule_available_days' => $availableDays,
            ])->save();

            $this->timeline->add($application, 'reschedule_requested', $applicant, ['reason' => $reason, 'available_days' => $availableDays]);
            $this->audit->log(AuditAction::Update, $interview, ['event' => 'reschedule_requested', 'reason' => $reason], $applicant);

            $name = $applicant->applicantProfile->full_name;

            $this->notifier->notify(
                [$interview->interviewer, $interview->scheduler],
                'Applicant asked to reschedule',
                "{$name} for {$application->jobTitle->title} cannot attend on {$interview->starts_at->format('M j, Y g:i A')}. Reason: {$reason} Available: {$availableDays}",
                route('recruitment.show', $application),
            );

            return $interview;
        });
    }
}
