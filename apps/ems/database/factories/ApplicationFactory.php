<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\JobTitle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->applicant(),
            'job_title_id' => JobTitle::factory(),
            'status' => ApplicationStatus::Applied,
            'applied_on' => now()->subDays(fake()->numberBetween(1, 20))->toDateString(),
            'education' => [['level' => 'Bachelor', 'school' => 'Pamantasan ng Lungsod ng Pasig', 'course' => 'BS Education', 'year_graduated' => '2018']],
            'work_history' => [['employer' => 'Sample School', 'position' => 'Teacher', 'from' => '2018-06', 'to' => '2024-03']],
        ];
    }

    public function status(ApplicationStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status, 'status_changed_at' => now()]);
    }
}
