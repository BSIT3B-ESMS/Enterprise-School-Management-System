<?php

namespace App\Actions\Employees;

use App\Actions\Accounts\AccountException;
use App\Actions\Accounts\DeactivateStaffAccount;
use App\Enums\AuditAction;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ChangeEmployeeStatus
{
    public function __construct(private AuditLogger $audit, private DeactivateStaffAccount $deactivateAccount) {}

    /**
     * Archives (Inactive, Resigned, Retired, or Terminated) or restores (Active) an employee. Nothing is ever deleted.
     * Archiving also deactivates the employee's login, if they have one; restoring does not turn the login back on.
     *
     * @throws EmployeeActionException
     */
    public function handle(User $actor, Employee $employee, EmployeeStatus $status, ?string $reason, ?CarbonInterface $effectiveOn): void
    {
        if ($status === $employee->status) {
            throw new EmployeeActionException("This employee is already {$status->value}.");
        }

        DB::transaction(function () use ($actor, $employee, $status, $reason, $effectiveOn): void {
            $employee->forceFill([
                'status' => $status,
                'status_reason' => $status === EmployeeStatus::Active ? null : $reason,
                'status_effective_on' => $status === EmployeeStatus::Active ? null : $effectiveOn?->toDateString(),
            ]);
            $changes = $this->audit->changesFor($employee);
            $employee->save();

            $this->audit->log(AuditAction::Update, $employee, ['event' => $status === EmployeeStatus::Active ? 'restored' : 'archived', ...$changes], $actor);

            if ($status !== EmployeeStatus::Active && $employee->user?->is_active) {
                try {
                    $this->deactivateAccount->handle($actor, $employee->user);
                } catch (AccountException $exception) {
                    throw new EmployeeActionException($exception->getMessage());
                }
            }
        });
    }
}
