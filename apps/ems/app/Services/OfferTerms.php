<?php

namespace App\Services;

use App\Enums\EmploymentType;
use App\Enums\OfferKind;
use App\Models\Benefit;
use App\Models\College;
use App\Models\JobTitle;
use App\Models\Office;
use App\Models\RateTier;
use Carbon\CarbonInterface;

/**
 * Builds the two parts of an offer: the summary of the position (what is being offered) and the terms and conditions the person
 * confirms by signing. HR can edit the terms before sending; this gives the standard wording of a professional or government
 * offer.
 */
class OfferTerms
{
    public function __construct(private AcademicCalendar $calendar) {}

    /**
     * The summary of the position as it stands when the offer is made. Pay is the base rate (Step 1) of the salary grade.
     *
     * @param  list<int>  $benefitIds
     * @return array<string, mixed>
     */
    public function summary(
        JobTitle $title,
        ?College $college,
        ?Office $office,
        EmploymentType $type,
        int $weeklyHours,
        CarbonInterface $start,
        ?CarbonInterface $end,
        array $benefitIds = [],
    ): array {
        $pay = RateTier::scheduleOn()[$title->salary_grade] ?? null;

        return [
            'position' => $title->title,
            'unit' => collect([$college?->name, $office?->name])->filter()->implode(' and '),
            'employment_type' => $type->label(),
            'weekly_hours' => $type === EmploymentType::PartTime ? $weeklyHours : EmployeeHours::FULL,
            'salary_grade' => $title->salary_grade,
            'monthly_pay' => $pay !== null ? (float) $pay : null,
            'start_date' => $start->toDateString(),
            'end_date' => $end?->toDateString(),
            'term' => $end ? $this->calendar->labelForEnd($end) : null,
            'benefits' => Benefit::query()->whereIn('id', $benefitIds)->orderBy('name')->pluck('name')->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    public function terms(OfferKind $kind, array $summary): string
    {
        $isPartTime = $summary['employment_type'] === EmploymentType::PartTime->label();
        $start = date('F j, Y', strtotime($summary['start_date']));
        $end = $summary['end_date'] ? date('F j, Y', strtotime($summary['end_date'])) : null;
        $pay = $summary['monthly_pay'] !== null ? '₱'.number_format($summary['monthly_pay'], 2).' a month (Salary Grade '.$summary['salary_grade'].', Step 1)' : 'the base pay of Salary Grade '.$summary['salary_grade'];
        $subject = match ($kind) {
            OfferKind::Hire => "an appointment as {$summary['position']}",
            OfferKind::Renewal => "a new contract as {$summary['position']}",
            OfferKind::Change => "a change of assignment to {$summary['position']}",
        };

        $clauses = [
            "Position and unit. The University offers you {$subject}, {$summary['unit']}, on a {$summary['employment_type']} basis.",
            "Start of contract. Your service under this offer starts on {$start}.",
            $isPartTime
                ? "End of contract. This is a fixed-term engagement ending on {$end}".($summary['term'] ? " ({$summary['term']})" : '').", for {$summary['weekly_hours']} hours a week. It ends on that date without further notice unless the University offers a new contract in writing."
                : 'End of contract. A full-time appointment has no fixed end date and continues until it ends under Civil Service rules and University policy.',
            "Compensation. Your monthly base pay is {$pay}. Government deductions, contributions, and taxes are withheld as the law requires.",
            'Benefits and leave. You receive the benefits stated in this offer and the leave credits your employment type is entitled to under the Civil Service leave rules and University policy.',
            'Duties and conduct. You will perform the duties of the position and follow the University rules, the Code of Conduct and Ethical Standards for Public Officials and Employees, and the Data Privacy Act.',
            'Requirements. This offer depends on the documents you submitted being true and complete. A false or misleading document is ground for withdrawing this offer or ending the engagement.',
            'Acceptance. By typing my full name below and accepting this offer, I confirm that I have read and understood it and that I agree to these terms. My typed name is my signature.',
        ];

        return collect($clauses)->map(fn (string $clause, int $index): string => ($index + 1).'. '.$clause)->implode("\n\n");
    }
}
