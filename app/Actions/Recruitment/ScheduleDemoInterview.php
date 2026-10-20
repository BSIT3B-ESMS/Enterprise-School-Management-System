<?php

namespace App\Actions\Recruitment;

use App\Enums\ApplicationStatus;
use App\Enums\AuditAction;
use App\Enums\InterviewMode;
use App\Enums\InterviewStatus;
use App\Enums\Role;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use App\Services\InterviewConflicts;
use App\Services\Notifier;
use App\Services\WorkingHours;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ScheduleDemoInterview
{
    public function __construct(
        private AuditLogger $audit,
        private ApplicationTimeline $timeline,
        private InterviewConflicts $conflicts,
        private Notifier $notifier,
    ) {}

    /**
     * Sets the demo interview of an application: when, where (a venue, or an online link), and which HR Staff or HR Admin
     * interviews. The applicant, the interviewer, and the college's dean and College Secretary are notified. An overlap with
     * another interview, the applicant's other interview, or the interviewer's leave stops the scheduling with the list of
     * conflicts and the free interviewers.
     *
     * @throws RecruitmentException
     * @throws InterviewConflictException
     */
    public function handle(
        User $actor,
        Application $application,
        User $interviewer,
        CarbonInterface $start,
        CarbonInterface $end,
        InterviewMode $mode,
        ?string $venue,
        ?string $meetingLink,
        ?string $notes = null,
    ): Interview {
        $this->validate($application, $interviewer, $start, $end, $mode, $venue, $meetingLink);

        $found = $this->conflicts->check($interviewer, $application, $start, $end);

        if ($found !== []) {
            throw new InterviewConflictException($found, $this->conflicts->freeInterviewers($application, $start, $end));
        }

        return DB::transaction(function () use ($actor, $application, $interviewer, $start, $end, $mode, $venue, $meetingLink, $notes): Interview {
            $interview = $application->interviews()->create([
                'interviewer_id' => $interviewer->id,
                'scheduled_by' => $actor->id,
                'starts_at' => $start,
                'ends_at' => $end,
                'mode' => $mode,
                'venue' => $mode === InterviewMode::FaceToFace ? $venue : null,
                'meeting_link' => $mode === InterviewMode::Online ? $meetingLink : null,
                'status' => InterviewStatus::Scheduled,
                'notes' => $notes,
            ]);

            $application->forceFill(['status' => ApplicationStatus::DemoInterview, 'status_changed_at' => now()])->save();

            $this->timeline->add($application, 'demo_scheduled', $actor, [
                'starts_at' => $start->toDateTimeString(),
                'interviewer' => $interviewer->name,
            ]);
            $this->audit->log(AuditAction::Create, $interview, ['application_id' => $application->id, 'starts_at' => $start->toDateTimeString()], $actor);

            $this->announce($application->loadMissing('applicant.applicantProfile', 'jobTitle.college'), $interview, $actor);

            return $interview;
        });
    }

    /**
     * @throws RecruitmentException
     */
    private function validate(Application $application, User $interviewer, CarbonInterface $start, CarbonInterface $end, InterviewMode $mode, ?string $venue, ?string $meetingLink): void
    {
        if (! in_array($application->status, [ApplicationStatus::Applied, ApplicationStatus::DemoInterview], true)) {
            throw new RecruitmentException("An application that is {$application->status->label()} cannot be given a demo interview.");
        }

        if ($application->interviews()->scheduled()->exists()) {
            throw new RecruitmentException('This application already has a demo interview scheduled. Reschedule or cancel it instead.');
        }

        if (! in_array($interviewer->role, [Role::HrAdmin, Role::HrStaff], true) || ! $interviewer->is_active) {
            throw new RecruitmentException('The interviewer must be an active HR Staff or HR Admin.');
        }

        if ($start->lte(now())) {
            throw new RecruitmentException('Choose a date and time that has not passed yet.');
        }

        if ($end->lte($start)) {
            throw new RecruitmentException('The interview must end after it starts.');
        }

        app(WorkingHours::class)->assertWithin($start, $end);

        if ($mode === InterviewMode::FaceToFace && blank($venue)) {
            throw new RecruitmentException('Give the venue where the interview will happen.');
        }

        if ($mode === InterviewMode::Online && blank($meetingLink)) {
            throw new RecruitmentException('Give the link the applicant will use for the online interview.');
        }
    }

    private function announce(Application $application, Interview $interview, User $actor): void
    {
        $name = $application->applicant->applicantProfile->full_name;
        $when = $interview->starts_at->format('M j, Y g:i A');
        $place = $interview->mode === InterviewMode::Online ? "online ({$interview->meeting_link})" : "at {$interview->venue}";

        $this->notifier->notify(
            $application->applicant,
            'Demo interview scheduled',
            "Your demo interview for {$application->jobTitle->title} is on {$when}, {$place}. You can ask to reschedule, but you cannot cancel within 24 hours of the interview.",
            route('applicant.applications.show', $application),
        );

        if ($interview->interviewer_id !== $actor->id) {
            $this->notifier->notify($interview->interviewer, 'You are assigned to a demo interview', "{$name} for {$application->jobTitle->title}, {$when}.", route('recruitment.show', $application));
        }

        $this->notifier->notify(
            $this->notifier->collegeLeaders($application->jobTitle->college),
            'Demo interview scheduled',
            "{$name} has a demo interview for {$application->jobTitle->title} on {$when}.",
            route('recruitment.show', $application),
        );
    }
}
