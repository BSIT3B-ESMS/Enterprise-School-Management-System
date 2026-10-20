<?php

namespace Database\Factories;

use App\Models\EmergencyContact;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmergencyContact>
 */
class EmergencyContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'name' => fake()->name(),
            'relationship' => fake()->randomElement(['Spouse', 'Parent', 'Sibling', 'Child']),
            'phone' => '09'.fake()->numerify('#########'),
            'address' => fake()->streetAddress().', Pasig City',
            'is_primary' => true,
        ];
    }
}
