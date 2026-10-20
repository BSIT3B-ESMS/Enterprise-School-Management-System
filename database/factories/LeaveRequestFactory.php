<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'leave_type' => LeaveType::Vacation,
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(8)->toDateString(),
            'days' => 2,
            'reason' => null,
            'status' => LeaveStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => LeaveStatus::Approved, 'decided_at' => now()]);
    }

    /**
     * Approved leave that covers today.
     */
    public function underway(): static
    {
        return $this->approved()->state(fn (): array => [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'days' => 3,
        ]);
    }
}
