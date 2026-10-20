<?php

namespace Database\Factories;

use App\Models\College;
use App\Models\CollegeTransfer;
use App\Models\Employee;
use App\Models\JobTitle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollegeTransfer>
 */
class CollegeTransferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'from_college_id' => College::factory(),
            'to_college_id' => College::factory(),
            'from_job_title_id' => JobTitle::factory(),
            'to_job_title_id' => JobTitle::factory(),
            'effective_date' => fake()->dateTimeBetween('-1 year', '+1 month')->format('Y-m-d'),
            'reason' => null,
        ];
    }
}
