<?php

namespace Database\Factories;

use App\Models\RateTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RateTier>
 */
class RateTierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'salary_grade' => 12,
            'monthly_amount' => 33947,
            'effective_date' => '2026-01-01',
        ];
    }
}
