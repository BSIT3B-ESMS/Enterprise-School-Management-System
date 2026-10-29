<?php

namespace Database\Factories;

use App\Models\College;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'college_id' => College::factory(),
            'code' => strtoupper(fake()->unique()->lexify('?????')),
            'name' => 'Bachelor of Science in '.fake()->words(2, true),
            'is_active' => true,
        ];
    }
}
