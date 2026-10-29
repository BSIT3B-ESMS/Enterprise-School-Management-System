<?php

namespace App\Actions\Leave;

use App\Enums\AuditAction;
use App\Enums\EmployeeStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Employee;
use App\Models\LeavePolicy;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LeaveBalances;
use App\Services\Notifier;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FileLeaveRequest
{
    /**
     * How far back a sick or emergency leave may start.
     */
    private const BACKDATE_DAYS = 30;

    public function __construct(private AuditLogger $audit, private LeaveBalances $balances, private Notifier $notifier) {}

    /**
     * Files a pending leave request for an employee. It is checked against the law's eligibility for the leave type, the
     * employee's remaining credits for the year, and their other pending or approved leave, and the days it uses are
     * worked out and stored with it.
     *
     * @throws ValidationException
     */
    public function handle(User $actor, Employee $employee, LeaveType $type, CarbonInterface $start, CarbonInterface $end, ?string $reason): LeaveRequest
    {
        $days = $this->validate($employee, $type, $start, $end);

        return DB::transaction(function () use ($actor, $employee, $type, $start, $end, $reason, $days): LeaveRequest {
            $request = LeaveRequest::query()->create([
                'employee_id' => $employee->id,
                'leave_type' => $type,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'days' => $days,
                'reason' => $reason,
                'status' => LeaveStatus::Pending,
            ]);

            $request->statusChanges()->create(['from_status' => null, 'to_status' => LeaveStatus::Pending, 'changed_by' => $actor->id]);
            $this->audit->log(AuditAction::Create, $request, [
                'employee_no' => $employee->employee_no,
                'leave_type' => $type->value,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'days' => $days,
            ], $actor);

            $this->notifier->notify(
                $this->notifier->hrAdmins()->reject(fn (User $admin): bool => $admin->id === $actor->id),
                'New leave request',
                "{$employee->full_name} filed {$type->label()} for {$days} day(s), {$start->format('M j')} to {$end->format('M j, Y')}.",
                route('leave.index'),
            );

            return $request;
        });
    }

    /**
     * @throws ValidationException
     */
    private function validate(Employee $employee, LeaveType $type, CarbonInterface $start, CarbonInterface $end): int
    {
        $fail = fn (string $field, string $message) => throw ValidationException::withMessages([$field => $message]);

        if ($employee->status !== EmployeeStatus::Active) {
            $fail('leave_type', 'Only an active employee can file leave.');
        }

        if (LeavePolicy::daysFor($employee, $type) === null) {
            $fail('leave_type', "You have no {$type->label()} credits.");
        }

        $earliest = $type->allowsBackdating() ? now()->subDays(self::BACKDATE_DAYS)->startOfDay() : now()->startOfDay();

        if ($start->lt($earliest)) {
            $fail('start_date', $type->allowsBackdating()
                ? 'This leave can start up to '.self::BACKDATE_DAYS.' days ago at the earliest.'
                : 'Choose today or a later date.');
        }

        if ($end->lt($start)) {
            $fail('end_date', 'The last day cannot be before the first day.');
        }

        if (! $this->balances->isAccruing($type) && $start->year !== $end->year) {
            $fail('end_date', 'This leave has yearly credits. File leave that crosses into a new year as two requests, one for each year.');
        }

        $days = $type->daysBetween($start, $end);

        if ($days < 1) {
            $fail('start_date', 'These dates are all weekend days, so no leave is needed.');
        }

        if (LeaveRequest::query()->where('employee_id', $employee->id)->overlapping($start->toDateString(), $end->toDateString())->exists()) {
            $fail('start_date', 'You already have leave on some of these days.');
        }

        $remaining = $this->balances->remaining($employee, $type, $start->year) ?? 0.0;

        if ($days > $remaining) {
            $fail('end_date', "This needs {$days} day(s) but only ".rtrim(rtrim(number_format($remaining, 1), '0'), '.').' day(s) of '.$type->label().' are left.');
        }

        return $days;
    }
}
