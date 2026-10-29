<?php

namespace Database\Factories;

use App\Enums\DataRequestStatus;
use App\Enums\DataRequestType;
use App\Models\DataRequest;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataRequest>
 */
class DataRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'type' => DataRequestType::Access,
            'details' => fake()->sentence(),
            'status' => DataRequestStatus::Pending,
        ];
    }
}
