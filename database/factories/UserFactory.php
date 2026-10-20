<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Role::Employee,
            'is_active' => true,
            'onboarding_completed_at' => now(),
        ];
    }

    /**
     * A new hire who has not yet done the first-login setup.
     */
    public function needsOnboarding(): static
    {
        return $this->state(fn (): array => ['onboarding_completed_at' => null]);
    }

    /**
     * An applicant account with its one-time profile.
     */
    public function applicant(): static
    {
        return $this->state(fn (): array => ['role' => Role::Applicant, 'onboarding_completed_at' => now()])
            ->afterCreating(function (User $user): void {
                $user->applicantProfile()->create([
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'birthdate' => fake()->dateTimeBetween('-40 years', '-22 years')->format('Y-m-d'),
                    'sex' => 'Female',
                    'marital_status' => 'Single',
                    'phone' => '09171234567',
                    'address' => 'Pasig City',
                ]);
            });
    }

    public function hrAdmin(): static
    {
        return $this->state(fn (): array => ['role' => Role::HrAdmin]);
    }

    public function hrStaff(): static
    {
        return $this->state(fn (): array => ['role' => Role::HrStaff]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
