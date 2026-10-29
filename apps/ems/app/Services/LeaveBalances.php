<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Employee;
use App\Models\LeavePolicy;
use App\Models\LeaveRequest;

/**
 * An employee's leave credits under the Civil Service leave rules. Vacation and sick leave are earned every month from
 * the hire date (a year's credits divided by 12, 1.25 days for 15 a year) and carry over, so the balance is everything
 * earned so far minus everything used or pending. Part-time staff earn in proportion to their weekly hours. The other
 * leaves are a fixed number of days per calendar year, counted by the year a request starts in. Credits are reference
 * data for HR; nothing here is payroll.
 */
class LeaveBalances
{
    /**
     * Leave types that are earned month by month and carry over from year to year.
     *
     * @var list<LeaveType>
     */
    public const ACCRUING = [LeaveType::Vacation, LeaveType::Sick];

    /**
     * One row per leave type the employee is entitled to. For vacation and sick leave "entitled" is what has been earned
     * so far; for the rest it is the year's allowance.
     *
     * @return list<array{type: LeaveType, label: string, accrues: bool, entitled: float, used: float, pending: float, remaining: float}>
     */
    public function forEmployee(Employee $employee, ?int $year = null): array
    {
        $year ??= now()->year;

        $requests = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', [LeaveStatus::Approved, LeaveStatus::Pending])
            ->get(['leave_type', 'status', 'start_date', 'days']);

        $rows = [];

        foreach (LeaveType::cases() as $type) {
            $yearly = LeavePolicy::daysFor($employee, $type);

            if ($yearly === null) {
                continue;
            }

            $accrues = in_array($type, self::ACCRUING, true);
            $counted = $requests->filter(fn (LeaveRequest $request): bool => $request->leave_type === $type && ($accrues || $request->start_date->year === $year));
            $entitled = $accrues ? $this->earned($employee, $yearly) : $yearly;
            $used = (float) $counted->where('status', LeaveStatus::Approved)->sum('days');
            $pending = (float) $counted->where('status', LeaveStatus::Pending)->sum('days');

            $rows[] = [
                'type' => $type,
                'label' => $type->label(),
                'accrues' => $accrues,
                'entitled' => $entitled,
                'used' => $used,
                'pending' => $pending,
                'remaining' => round($entitled - $used - $pending, 2),
            ];
        }

        return $rows;
    }

    public function isAccruing(LeaveType $type): bool
    {
        return in_array($type, self::ACCRUING, true);
    }

    /**
     * Credits left for one leave type, or null when the employee has no credits of that type. For a year-based leave the
     * year is the one the request starts in.
     */
    public function remaining(Employee $employee, LeaveType $type, int $year): ?float
    {
        foreach ($this->forEmployee($employee, $year) as $row) {
            if ($row['type'] === $type) {
                return $row['remaining'];
            }
        }

        return null;
    }

    /**
     * What an employee has earned from their hire date to today: whole months worked times the monthly rate, scaled by
     * their share of full-time hours. Nothing is earned before the hire date, or without one.
     */
    public function earned(Employee $employee, float $daysPerYear): float
    {
        if ($employee->hired_on === null || $employee->hired_on->isFuture()) {
            return 0.0;
        }

        $months = (int) floor($employee->hired_on->startOfDay()->diffInMonths(now()->startOfDay(), absolute: true));

        return round($months * ($daysPerYear / 12) * $employee->leaveCreditShare(), 2);
    }
}
