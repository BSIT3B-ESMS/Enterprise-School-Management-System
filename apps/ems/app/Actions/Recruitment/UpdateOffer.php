<?php

namespace App\Actions\Recruitment;

use App\Enums\ApplicationStatus;
use App\Enums\AuditAction;
use App\Enums\EmploymentType;
use App\Enums\OfferKind;
use App\Models\Offer;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use App\Services\EmployeeHours;
use App\Services\Notifier;
use App\Services\OfferTerms;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class UpdateOffer
{
    public function __construct(
        private AuditLogger $audit,
        private ApplicationTimeline $timeline,
        private Notifier $notifier,
        private OfferTerms $terms,
    ) {}

    /**
     * The HR Admin edits an offer the applicant has not answered yet. The summary is rebuilt from the new choices, and the standard
     * terms are rebuilt too unless HR wrote its own. The applicant is told the offer changed. An accepted or declined offer is final.
     *
     * @param  list<int>  $benefitIds
     *
     * @throws RecruitmentException
     */
    public function handle(
        User $actor,
        Offer $offer,
        EmploymentType $type,
        int $weeklyHours,
        CarbonInterface $start,
        ?CarbonInterface $end,
        array $benefitIds = [],
        ?string $terms = null,
    ): Offer {
        if (! $actor->isHrAdmin()) {
            throw new RecruitmentException('Only an HR Admin can edit an offer.');
        }

        $application = $offer->application()->with('jobTitle.college', 'jobTitle.office', 'applicant')->firstOrFail();

        if ($offer->kind !== OfferKind::Hire || ! $offer->isPending() || $application->status !== ApplicationStatus::Offered) {
            throw new RecruitmentException('Only an offer that is still waiting for an answer can be edited.');
        }

        if ($start->copy()->startOfDay()->lt(now()->startOfDay())) {
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

        return DB::transaction(function () use ($actor, $offer, $application, $type, $weeklyHours, $start, $end, $benefitIds, $terms, $summary): Offer {
            $offer->forceFill([
                'employment_type' => $type,
                'weekly_hours' => $weeklyHours,
                'start_date' => $start->toDateString(),
                'end_date' => $end?->toDateString(),
                'summary' => $summary,
                'terms' => filled($terms) ? $terms : $this->terms->terms(OfferKind::Hire, $summary),
                'benefit_ids' => array_values($benefitIds),
            ]);
            $changes = $this->audit->changesFor($offer);
            $offer->save();

            $this->timeline->add($application, 'offer_updated', $actor, ['start_date' => $start->toDateString(), 'end_date' => $end?->toDateString()]);
            $this->audit->log(AuditAction::Update, $offer, ['event' => 'offer_updated', ...array_diff_key($changes, ['terms' => 1, 'summary' => 1])], $actor);

            $this->notifier->notify(
                $application->applicant,
                'Your job offer was updated',
                "PLP changed the offer for {$application->jobTitle->title}. Read it again before you answer.",
                route('applicant.applications.show', $application),
            );

            return $offer;
        });
    }
}
