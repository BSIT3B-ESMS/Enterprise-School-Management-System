<?php

namespace App\Actions\Employees;

use App\Enums\AuditAction;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogger;

class RevealGovernmentId
{
    /**
     * The reasons the HR Head can give for looking at a full government ID number.
     *
     * @var array<string, string>
     */
    public const REASONS = [
        'verification' => 'Verification',
        'government_filing' => 'Government filing',
        'employee_request' => 'Employee request',
        'other' => 'Other',
    ];

    public function __construct(private AuditLogger $audit) {}

    /**
     * Returns the full number and writes the reveal, with its reason, to the audit log. Nothing is returned without a log.
     *
     * @throws EmployeeActionException
     */
    public function handle(User $actor, Employee $employee, string $type, string $reason): string
    {
        $number = $employee->government_ids[$type] ?? null;

        if ($number === null) {
            throw new EmployeeActionException('There is no number on file for that ID.');
        }

        $this->audit->log(AuditAction::Reveal, $employee, ['id_type' => $type, 'reason' => $reason], $actor);

        return $number;
    }
}
