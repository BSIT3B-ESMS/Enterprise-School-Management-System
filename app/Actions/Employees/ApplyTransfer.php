<?php

namespace App\Actions\Employees;

use App\Enums\AuditAction;
use App\Models\CollegeTransfer;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class ApplyTransfer
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Moves the employee to the college, office, and job title the transfer records, and marks the transfer applied.
     * The transfer row itself is never edited otherwise: it stays as the history of the move.
     */
    public function handle(CollegeTransfer $transfer, ?User $actor = null): void
    {
        DB::transaction(function () use ($transfer, $actor): void {
            $employee = $transfer->employee;

            $employee->forceFill([
                'college_id' => $transfer->to_college_id,
                'office_id' => $transfer->to_office_id,
                'job_title_id' => $transfer->to_job_title_id,
            ]);
            $changes = $this->audit->changesFor($employee);
            $employee->save();

            $transfer->forceFill(['applied_at' => now()])->save();

            $this->audit->log(AuditAction::Update, $employee, ['event' => 'transfer_applied', ...$changes], $actor);
        });
    }
}
