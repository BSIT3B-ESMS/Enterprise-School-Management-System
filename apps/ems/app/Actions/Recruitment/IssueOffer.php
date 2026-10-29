<?php

namespace App\Actions\Recruitment;

use App\Enums\ApplicationStatus;
use App\Enums\AuditAction;
use App\Enums\EmploymentType;
use App\Enums\InterviewStatus;
use App\Enums\OfferKind;
use App\Models\Application;
use App\Models\Employee;
use App\Models\Offer;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use App\Services\EmployeeHours;
use App\Services\Notifier;
use App\Services\OfferTerms;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class IssueOffer
{
    public function __construct(
        private AuditLogger $audit,
        private ApplicationTimeline $timeline,
        private Notifier $notifier,
        private OfferTerms $terms,
    ) {}

    /**
     * The HR Admin sends an offer after the demo interview: the summary of the position, the terms, the confirmed start of
     * contract, and (for a part-time post) the end of contract by semester or academic year. A full-time offer has no end
     * date. The applicant answers it in their account.
     *
     * @param  list<int>  $benefitIds
     *
     * @throws RecruitmentException
     */
    public function handle(
        User $actor,
        Application $application,
        EmploymentType $type,
        int $weeklyHours,
        CarbonInterface $start,
        ?CarbonInterface $end,
        array $benefitIds = [],
        ?string $terms = null,
    ): Offer {
        if (! $actor->isHrAdmin()) {
            throw new RecruitmentException('Only an HR Admin can issue an offer.');
        }

        $application->loadMissing('jobTitle.college', 'jobTitle.office', 'applicant.applicantProfile', 'interviews', 'offers');

        if ($application->status !== ApplicationStatus::DemoInterview) {
            throw new RecruitmentException('An offer can be issued only after the demo interview.');
        }

        if ($application->interviews->firstWhere('status', InterviewStatus::Scheduled) || ! $application->interviews->firstWhere('status', InterviewStatus::Completed)) {
            throw new RecruitmentException('Mark the demo interview as completed before issuing an offer.');
        }

        if ($start->startOfDay()->lt(now()->startOfDay())) {
            throw new RecruitmentException('The start of contract cannot be in the past.');
        }

        if ($type === EmploymentType::PartTime) {
            if ($end === null) {
                throw new RecruitmentException('A part-time offer needs an end of contract (the end of a semester or the academic year).');
            }

            if ($end->lte($start)) {
                throw new RecruitmentException('The end of contract must be after the start.');
            }

            if ($weeklyHours < 1 || $weeklyHours >= EmployeeHours::FULL) {
                throw new RecruitmentException('Part-time weekly hours must be between 1 and 39.');
            }
        } elseif ($end !== null) {
            throw new RecruitmentException('A full-time offer has no end of contract.');
        }

        $weeklyHours = $type === EmploymentType::PartTime ? $weeklyHours : EmployeeHours::FULL;
        $title = $application->jobTitle;
        $summary = $this->terms->summary($title, $title->college, $title->office, $type, $weeklyHours, $start, $end, $benefitIds);

        return DB::transaction(function () use ($actor, $application, $type, $weeklyHours, $start, $end, $benefitIds, $terms, $title, $summary): Offer {
            $application->offers()->pending()->update(['status' => 'withdrawn', 'responded_at' => now()]);

            $offer = $application->offers()->create([
                'kind' => OfferKind::Hire,
                'issued_by' => $actor->id,
                'employment_type' => $type,
                'weekly_hours' => $weeklyHours,
                'job_title_id' => $title->id,
                ...Employee::unitsFor($title),
                'start_date' => $start->toDateString(),
                'end_date' => $end?->toDateString(),
                'summary' => $summary,
                'terms' => filled($terms) ? $terms : $this->terms->terms(OfferKind::Hire, $summary),
                'benefit_ids' => array_values($benefitIds),
            ]);

            $application->forceFill(['status' => ApplicationStatus::Offered, 'status_changed_at' => now()])->save();

            $this->timeline->add($application, 'offer_issued', $actor, ['start_date' => $start->toDateString(), 'end_date' => $end?->toDateString()]);
            $this->audit->log(AuditAction::Create, $offer, ['application_id' => $application->id, 'employment_type' => $type->value], $actor);

            $this->notifier->notify(
                $application->applicant,
                'You have a job offer',
                "PLP sent you an offer for {$title->title}. Read it and accept or decline in your account.",
                route('applicant.applications.show', $application),
            );

            return $offer;
        });
    }
}
