<?php

namespace Database\Factories;

use App\Models\College;
use App\Models\CollegeDean;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollegeDean>
 */
class CollegeDeanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'college_id' => College::factory(),
            'employee_id' => Employee::factory(),
            'started_on' => null,
            'ended_on' => null,
        ];
    }
}
