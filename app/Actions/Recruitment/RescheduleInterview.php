<?php

namespace App\Actions\Recruitment;

use App\Enums\AuditAction;
use App\Enums\InterviewMode;
use App\Enums\InterviewStatus;
use App\Models\Interview;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use App\Services\InterviewConflicts;
use App\Services\Notifier;
use App\Services\WorkingHours;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RescheduleInterview
{
    public function __construct(
        private AuditLogger $audit,
        private ApplicationTimeline $timeline,
        private InterviewConflicts $conflicts,
        private Notifier $notifier,
    ) {}

    /**
     * Moves a demo interview that has not happened yet. A reason is always required (it is kept in the timeline and shown to
     * the applicant). The time may not be in the past. The applicant, the interviewer (when someone else moved it), and the
     * college's leaders are told. The venue or link may be changed in the same step.
     *
     * @throws RecruitmentException
     * @throws InterviewConflictException
     */
    public function handle(
        User $actor,
        Interview $interview,
        CarbonInterface $start,
        CarbonInterface $end,
        string $reason,
        ?InterviewMode $mode = null,
        ?string $venue = null,
        ?string $meetingLink = null,
    ): Interview {
        if ($interview->status !== InterviewStatus::Scheduled) {
            throw new RecruitmentException("A {$interview->status->value} interview cannot be rescheduled.");
        }

        if (blank($reason)) {
            throw new RecruitmentException('Give a reason for moving the interview.');
        }

        if ($start->lte(now())) {
            throw new RecruitmentException('Choose a date and time that has not passed yet.');
        }

        if ($end->lte($start)) {
            throw new RecruitmentException('The interview must end after it starts.');
        }

        app(WorkingHours::class)->assertWithin($start, $end);

        $mode ??= $interview->mode;

        if ($mode === InterviewMode::FaceToFace && blank($venue ?? $interview->venue)) {
            throw new RecruitmentException('Give the venue where the interview will happen.');
        }

        if ($mode === InterviewMode::Online && blank($meetingLink ?? $interview->meeting_link)) {
            throw new RecruitmentException('Give the link the applicant will use for the online interview.');
        }

        $application = $interview->application()->with('applicant.applicantProfile', 'jobTitle.college')->firstOrFail();
        $found = $this->conflicts->check($interview->interviewer, $application, $start, $end, $interview);

        if ($found !== []) {
            throw new InterviewConflictException($found, $this->conflicts->freeInterviewers($application, $start, $end, $interview));
        }

        return DB::transaction(function () use ($actor, $interview, $application, $start, $end, $reason, $mode, $venue, $meetingLink): Interview {
            $old = $interview->starts_at->toDateTimeString();

            $interview->forceFill([
                'starts_at' => $start,
                'ends_at' => $end,
                'mode' => $mode,
                'venue' => $mode === InterviewMode::FaceToFace ? ($venue ?? $interview->venue) : null,
                'meeting_link' => $mode === InterviewMode::Online ? ($meetingLink ?? $interview->meeting_link) : null,
                'reschedule_requested_at' => null,
                'reschedule_reason' => null,
                'reschedule_available_days' => null,
            ])->save();

            $this->timeline->add($application, 'demo_rescheduled', $actor, ['from' => $old, 'to' => $start->toDateTimeString(), 'reason' => $reason]);
            $this->audit->log(AuditAction::Update, $interview, ['event' => 'interview_rescheduled', 'from' => $old, 'to' => $start->toDateTimeString(), 'reason' => $reason], $actor);

            $name = $application->applicant->applicantProfile->full_name;
            $when = $start->format('M j, Y g:i A');

            $this->notifier->notify($application->applicant, 'Demo interview rescheduled', "Your demo interview for {$application->jobTitle->title} moved to {$when}. Reason: {$reason}", route('applicant.applications.show', $application));

            if ($interview->interviewer_id !== $actor->id) {
                $this->notifier->notify($interview->interviewer, 'Demo interview rescheduled', "{$name} for {$application->jobTitle->title} moved to {$when}. Reason: {$reason}", route('recruitment.show', $application));
            }

            $this->notifier->notify($this->notifier->collegeLeaders($application->jobTitle->college), 'Demo interview rescheduled', "{$name} for {$application->jobTitle->title} moved to {$when}.", route('recruitment.show', $application));

            return $interview;
        });
    }
}
