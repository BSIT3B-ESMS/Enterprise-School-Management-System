<?php

namespace App\Actions\Employees;

use App\Enums\AuditAction;
use App\Models\CollegeTransfer;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class TransferEmployee
{
    public function __construct(private AuditLogger $audit, private ApplyTransfer $applyTransfer) {}

    /**
     * Records a change of college, office, or job title as a transfer, written even when only the college changes and
     * the title stays. A transfer dated today or earlier takes effect at once; a future one waits for the daily job
     * (see App\Console\Commands\ApplyDueTransfers). The employee's record is never rewritten without a transfer row.
     *
     * @throws EmployeeActionException
     */
    public function handle(
        User $actor,
        Employee $employee,
        JobTitle $jobTitle,
        ?int $extraCollegeId,
        ?int $extraOfficeId,
        CarbonInterface $effectiveDate,
        ?string $reason,
    ): CollegeTransfer {
        $target = Employee::unitsFor($jobTitle, $extraCollegeId, $extraOfficeId);

        if ($target['college_id'] === $employee->college_id && $target['office_id'] === $employee->office_id && $jobTitle->is($employee->jobTitle)) {
            throw new EmployeeActionException('That is the current assignment. Choose a different job title, college, or office.');
        }

        if ($employee->transfers()->whereNull('applied_at')->exists()) {
            throw new EmployeeActionException('This employee already has a transfer waiting to take effect. Wait for it first.');
        }

        return DB::transaction(function () use ($actor, $employee, $jobTitle, $target, $effectiveDate, $reason): CollegeTransfer {
            $transfer = $employee->transfers()->create([
                'from_college_id' => $employee->college_id,
                'to_college_id' => $target['college_id'],
                'from_office_id' => $employee->office_id,
                'to_office_id' => $target['office_id'],
                'from_job_title_id' => $employee->job_title_id,
                'to_job_title_id' => $jobTitle->id,
                'effective_date' => $effectiveDate->toDateString(),
                'reason' => $reason,
                'recorded_by' => $actor->id,
            ]);

            $this->audit->log(AuditAction::Create, $transfer, [
                'employee_no' => $employee->employee_no,
                'effective_date' => $effectiveDate->toDateString(),
            ], $actor);

            if ($effectiveDate->lte(now()->endOfDay())) {
                $this->applyTransfer->handle($transfer, $actor);
            }

            return $transfer;
        });
    }
}
