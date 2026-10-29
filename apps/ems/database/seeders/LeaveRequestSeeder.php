<?php

namespace Database\Seeders;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Database\Seeder;

/**
 * A few fictional leave requests on the five fictional office staff, so the leave pages have something to show: leave
 * under way today, a pending request, a rejection with its reason, and a finished one. The real faculty roster gets none.
 */
class LeaveRequestSeeder extends Seeder
{
    public function run(): void
    {
        $staff = Employee::query()->whereHas('office')->whereNotNull('hired_on')->orderBy('id')->get();

        if ($staff->count() < 4) {
            return;
        }

        $this->request($staff[0], LeaveType::Vacation, now()->subDay(), now()->addDay(), 3, LeaveStatus::Approved);
        $this->request($staff[1], LeaveType::Sick, now()->addDays(2), now()->addDays(3), 2, LeaveStatus::Pending, 'Medical check-up');
        $this->request($staff[2], LeaveType::Vacation, now()->addDays(14), now()->addDays(18), 5, LeaveStatus::Rejected, 'Family trip', 'Too many staff are away that week.');
        $this->request($staff[3], LeaveType::Emergency, now()->subDays(20), now()->subDays(19), 2, LeaveStatus::Approved);
    }

    private function request(Employee $employee, LeaveType $type, $start, $end, float $days, LeaveStatus $status, ?string $reason = null, ?string $note = null): void
    {
        $request = LeaveRequest::query()->create([
            'employee_id' => $employee->id,
            'leave_type' => $type,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days' => $days,
            'reason' => $reason,
            'status' => $status,
            'decided_at' => $status === LeaveStatus::Pending ? null : now(),
            'decision_note' => $note,
        ]);

        $request->statusChanges()->create(['from_status' => null, 'to_status' => LeaveStatus::Pending]);

        if ($status !== LeaveStatus::Pending) {
            $request->statusChanges()->create(['from_status' => LeaveStatus::Pending, 'to_status' => $status, 'note' => $note]);
        }
    }
}
