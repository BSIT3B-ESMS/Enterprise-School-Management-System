<?php

namespace App\Actions\Employees;

use App\Enums\AuditAction;
use App\Enums\EmploymentType;
use App\Enums\OfferKind;
use App\Enums\OfferStatus;
use App\Models\College;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Offer;
use App\Models\Office;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EmployeeHours;
use App\Services\Notifier;
use App\Services\OfferTerms;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ProposeAgreement
{
    public function __construct(private AuditLogger $audit, private Notifier $notifier, private OfferTerms $terms) {}

    /**
     * The HR Admin offers an employee a new contract (kind renewal, when the contract is ending) or a change of employment type,
     * job title, college, or office (kind change). Nothing changes until the employee accepts by signing. Declining leaves
     * them as they are. A part-time offer needs an end of contract; a full-time one has none.
     *
     * @param  list<int>  $benefitIds
     *
     * @throws EmployeeActionException
     */
    public function handle(
        User $actor,
        Employee $employee,
        OfferKind $kind,
        JobTitle $jobTitle,
        ?int $extraCollegeId,
        ?int $extraOfficeId,
        EmploymentType $type,
        int $weeklyHours,
        CarbonInterface $start,
        ?CarbonInterface $end,
        ?string $reason = null,
        array $benefitIds = [],
    ): Offer {
        if (! $actor->isHrAdmin()) {
            throw new EmployeeActionException('Only an HR Admin can send this.');
        }

        if ($kind === OfferKind::Hire) {
            throw new EmployeeActionException('Use the recruitment flow to hire an applicant.');
        }

        $employee->loadMissing('jobTitle', 'college', 'office', 'user');

        if ($employee->offers()->pending()->exists()) {
            throw new EmployeeActionException('This employee already has an offer waiting for an answer. Wait for it, or withdraw it first.');
        }

        if ($start->startOfDay()->lt(now()->startOfDay())) {
            throw new EmployeeActionException('The start date cannot be in the past.');
        }

        if ($type === EmploymentType::PartTime) {
            if ($end === null) {
                throw new EmployeeActionException('A part-time agreement needs an end of contract (the end of a semester or the academic year).');
            }

            if ($end->lte($start)) {
                throw new EmployeeActionException('The end of contract must be after the start.');
            }

            if ($weeklyHours < 1 || $weeklyHours >= EmployeeHours::FULL) {
                throw new EmployeeActionException('Part-time weekly hours must be between 1 and 39.');
            }
        } elseif ($end !== null) {
            throw new EmployeeActionException('A full-time agreement has no end of contract.');
        }

        $weeklyHours = $type === EmploymentType::PartTime ? $weeklyHours : EmployeeHours::FULL;
        $target = Employee::unitsFor($jobTitle, $extraCollegeId, $extraOfficeId);

        $unchanged = $kind === OfferKind::Change
            && $jobTitle->is($employee->jobTitle)
            && $target['college_id'] === $employee->college_id
            && $target['office_id'] === $employee->office_id
            && $type === $employee->employment_type
            && ($type === EmploymentType::FullTime || $end?->isSameDay($employee->contract_ends_on) === true);

        if ($unchanged) {
            throw new EmployeeActionException('That is the current assignment and employment type. Change something first.');
        }

        $summary = $this->terms->summary($jobTitle, College::query()->find($target['college_id']), Office::query()->find($target['office_id']), $type, $weeklyHours, $start, $end, $benefitIds);
        $summary['current'] = [
            'position' => $employee->jobTitle->title,
            'unit' => collect([$employee->college?->name, $employee->office?->name])->filter()->implode(' and '),
            'employment_type' => $employee->employment_type->label(),
            'weekly_hours' => $employee->weekly_hours,
            'end_date' => $employee->contract_ends_on?->toDateString(),
        ];

        return DB::transaction(function () use ($actor, $employee, $kind, $jobTitle, $target, $type, $weeklyHours, $start, $end, $reason, $benefitIds, $summary): Offer {
            $offer = $employee->offers()->create([
                'kind' => $kind,
                'issued_by' => $actor->id,
                'employment_type' => $type,
                'weekly_hours' => $weeklyHours,
                'job_title_id' => $jobTitle->id,
                'college_id' => $target['college_id'],
                'office_id' => $target['office_id'],
                'start_date' => $start->toDateString(),
                'end_date' => $end?->toDateString(),
                'summary' => $summary,
                'terms' => $this->terms->terms($kind, $summary),
                'benefit_ids' => array_values($benefitIds),
                'reason' => $reason,
                'status' => OfferStatus::Pending,
            ]);

            $this->audit->log(AuditAction::Create, $offer, ['kind' => $kind->value, 'employee_no' => $employee->employee_no], $actor);

            $this->notifier->notify(
                $employee->user,
                $kind === OfferKind::Renewal ? 'You have a new contract offer' : 'You have a change of assignment to confirm',
                "{$kind->label()}: {$summary['position']}, starting {$start->format('M j, Y')}. Read it and accept or decline. If you decline, nothing changes.",
                route('employee.offers.show', $offer),
            );

            return $offer;
        });
    }
}
