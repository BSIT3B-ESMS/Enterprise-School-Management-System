<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\LeaveStatusChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveStatusChange>
 */
class LeaveStatusChangeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'leave_request_id' => LeaveRequest::factory(),
            'from_status' => null,
            'to_status' => LeaveStatus::Pending,
            'changed_by' => null,
            'note' => null,
        ];
    }
}
