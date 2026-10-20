<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\LeaveType;
use App\Models\LeavePolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeavePolicy>
 */
class LeavePolicyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'leave_type' => LeaveType::Vacation,
            'employment_type' => EmploymentType::FullTime,
            'job_title_id' => null,
            'days_per_year' => 15,
            'is_active' => true,
        ];
    }
}
