<?php

namespace Database\Factories;

use App\Enums\InterviewMode;
use App\Enums\InterviewStatus;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interview>
 */
class InterviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(5)->setTime(10, 0);

        return [
            'application_id' => Application::factory(),
            'interviewer_id' => User::factory()->hrStaff(),
            'scheduled_by' => User::factory()->hrStaff(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
            'mode' => InterviewMode::FaceToFace,
            'venue' => 'HR Office, Admin Building',
            'status' => InterviewStatus::Scheduled,
        ];
    }
}
