<?php

namespace App\Actions\Accounts;

use App\Enums\AuditAction;
use App\Enums\EmploymentType;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogger;

/**
 * An HR Admin or HR Staff account must belong to a full-time employee. The leave rules key off employment_type, so a
 * part-time HR account would earn part-time leave instead of the full-time credits. Called whenever an HR role is granted.
 */
class EnsureHrEmployment
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(User $actor, Employee $employee): void
    {
        if ($employee->employment_type === EmploymentType::FullTime
            && (int) $employee->weekly_hours === Employee::FULL_TIME_HOURS
            && $employee->contract_ends_on === null) {
            return;
        }

        $employee->forceFill([
            'employment_type' => EmploymentType::FullTime,
            'weekly_hours' => Employee::FULL_TIME_HOURS,
            'contract_ends_on' => null,
        ]);

        $changes = $this->audit->changesFor($employee);
        $employee->save();

        $this->audit->log(AuditAction::Update, $employee, ['event' => 'hr_full_time', ...$changes], $actor);
    }
}
