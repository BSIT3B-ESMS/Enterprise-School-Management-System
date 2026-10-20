<?php

namespace App\Actions\Employees;

use App\Enums\AuditAction;
use App\Enums\EmploymentType;
use App\Enums\OfferKind;
use App\Enums\OfferStatus;
use App\Models\Employee;
use App\Models\Offer;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;

class ApplyAgreement
{
    public function __construct(private AuditLogger $audit, private TransferEmployee $transfer, private Notifier $notifier) {}

    /**
     * Puts an accepted contract offer or change of assignment into effect: the new employment type, weekly hours, and contract
     * end, and (for a change) the new job title, college, or office, recorded as a transfer. It runs once, on or after the
     * start date, and marks the offer applied.
     */
    public function handle(Offer $offer, ?User $actor = null): void
    {
        if ($offer->status !== OfferStatus::Accepted || $offer->applied_at !== null || ! in_array($offer->kind, [OfferKind::Renewal, OfferKind::Change], true)) {
            return;
        }

        DB::transaction(function () use ($offer, $actor): void {
            $employee = $offer->employee()->with(['jobTitle', 'user'])->firstOrFail();

            if ($offer->kind === OfferKind::Change && $this->assignmentDiffers($offer, $employee)) {
                $this->transfer->handle(
                    $actor ?? $offer->issuer,
                    $employee,
                    $offer->jobTitle,
                    $offer->jobTitle->college_id === null ? $offer->college_id : null,
                    $offer->jobTitle->office_id === null ? $offer->office_id : null,
                    $offer->start_date,
                    $offer->reason ?: 'Agreed change of assignment',
                );
                $employee->refresh();
            }

            $isHr = $employee->hasHrAccount();
            $type = $isHr ? EmploymentType::FullTime : $offer->employment_type;

            $employee->forceFill([
                'employment_type' => $type,
                'weekly_hours' => $type === EmploymentType::PartTime ? $offer->weekly_hours : Employee::FULL_TIME_HOURS,
                'contract_ends_on' => $type === EmploymentType::PartTime ? $offer->end_date : null,
            ]);
            $changes = $this->audit->changesFor($employee);
            $employee->save();

            $offer->forceFill(['applied_at' => now()])->save();

            $payload = ['event' => 'agreement_applied', 'offer_id' => $offer->id, ...$changes];

            if ($isHr && $offer->employment_type === EmploymentType::PartTime) {
                $payload['hr_full_time_override'] = true;
            }

            $this->audit->log(AuditAction::Update, $employee, $payload, $actor ?? $offer->issuer);
            $this->notifier->notify($employee->user, 'Your new terms are in effect', "{$offer->kind->label()}: {$offer->summary['position']} starting {$offer->start_date->format('M j, Y')}.", route('employee.dashboard'));
        });
    }

    private function assignmentDiffers(Offer $offer, Employee $employee): bool
    {
        return $offer->job_title_id !== $employee->job_title_id
            || $offer->college_id !== $employee->college_id
            || $offer->office_id !== $employee->office_id;
    }
}
