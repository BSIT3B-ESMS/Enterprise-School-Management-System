<?php

namespace App\Actions\Employees;

use App\Enums\AuditAction;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CreateEmployee
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Adds an employee: the next employee number, the official email built from the name, the college and office that
     * come from the job title, and any government ID numbers (stored encrypted). The number is retried if two people are
     * added at the same moment.
     *
     * @param  array<string, mixed>  $attributes  Personal fields plus employment_type, job_title_id, salary_step, and optional extra_college_id / extra_office_id.
     * @param  array<string, string>  $governmentIds
     */
    public function handle(User $actor, array $attributes, array $governmentIds = []): Employee
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(fn (): Employee => $this->create($actor, $attributes, $governmentIds));
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= 3) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, string>  $governmentIds
     */
    private function create(User $actor, array $attributes, array $governmentIds): Employee
    {
        $title = JobTitle::query()->findOrFail($attributes['job_title_id']);
        $extraCollege = $attributes['extra_college_id'] ?? null;
        $extraOffice = $attributes['extra_office_id'] ?? null;
        unset($attributes['extra_college_id'], $attributes['extra_office_id']);

        $employee = new Employee;
        $employee->forceFill([
            ...$attributes,
            ...Employee::unitsFor($title, $extraCollege, $extraOffice),
            'employee_no' => Employee::nextEmployeeNo(),
            'official_email' => Employee::officialEmailFor($attributes['last_name'], $attributes['first_name']),
            'status' => EmployeeStatus::Active,
        ]);
        $employee->setGovernmentIds($governmentIds);
        $employee->save();

        $this->audit->log(AuditAction::Create, $employee, [
            'employee_no' => $employee->employee_no,
            'job_title_id' => $employee->job_title_id,
            'government_ids_entered' => array_keys($governmentIds),
        ], $actor);

        return $employee;
    }
}
