<?php

namespace Database\Factories;

use App\Enums\JobTitleCategory;
use App\Models\College;
use App\Models\JobTitle;
use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobTitle>
 */
class JobTitleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->jobTitle(),
            'category' => JobTitleCategory::Faculty,
            'college_id' => College::factory(),
            'office_id' => null,
            'salary_grade' => 12,
            'is_active' => true,
            'openings' => 0,
            'is_published' => false,
        ];
    }

    /**
     * A non-teaching title that belongs to an office instead of a college.
     */
    public function nonTeaching(): static
    {
        return $this->state(fn (): array => [
            'category' => JobTitleCategory::NonTeaching,
            'college_id' => null,
            'office_id' => Office::factory(),
        ]);
    }
}
