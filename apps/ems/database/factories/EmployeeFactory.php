<?php

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\MaritalStatus;
use App\Enums\Sex;
use App\Models\Employee;
use App\Models\JobTitle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->lastName(),
            'birthdate' => fake()->dateTimeBetween('-60 years', '-25 years')->format('Y-m-d'),
            'sex' => fake()->randomElement(Sex::cases()),
            'marital_status' => fake()->randomElement(MaritalStatus::cases()),
            'employment_type' => EmploymentType::FullTime,
            'hired_on' => fake()->dateTimeBetween('-15 years', '-1 month')->format('Y-m-d'),
            'employee_no' => fn (array $attributes) => substr($attributes['hired_on'], 2, 2).'-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'job_title_id' => JobTitle::factory(),
            'college_id' => fn (array $attributes) => JobTitle::query()->find($attributes['job_title_id'])->college_id,
            'office_id' => fn (array $attributes) => JobTitle::query()->find($attributes['job_title_id'])->office_id,
            'official_email' => fake()->unique()->userName().'@plpasig.edu.ph',
            'phone' => '09'.fake()->numerify('#########'),
            'address' => fake()->streetAddress().', Pasig City',
            'status' => EmployeeStatus::Active,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Employee $employee): void {
            $employee->setGovernmentIds([
                'tin' => fake()->numerify('###-###-###-000'),
                'philhealth' => fake()->numerify('##-#########-#'),
                'pagibig' => fake()->numerify('####-####-####'),
            ]);
        });
    }

    public function partTime(): static
    {
        return $this->state(fn (): array => ['employment_type' => EmploymentType::PartTime, 'weekly_hours' => 20]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => EmployeeStatus::Inactive]);
    }

    /**
     * An employee in an office with a non-teaching title.
     */
    public function nonTeaching(): static
    {
        return $this->state(fn (): array => ['job_title_id' => JobTitle::factory()->nonTeaching()]);
    }
}
