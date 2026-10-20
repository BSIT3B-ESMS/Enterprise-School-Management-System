<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * A few HR calendar events around today, some shared with every employee.
 */
class CalendarEventSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::query()->where('role', Role::HrAdmin)->orderBy('id')->first();

        $events = [
            ['HR staff meeting', 'meeting', now()->addDays(2)->setTime(14, 0), 'HR Office', false],
            ['Data Privacy Act orientation', 'training', now()->addDays(9)->setTime(9, 0), 'AVR, Administration Building', true],
            ['Leave credit review', 'deadline', now()->addDays(15)->setTime(17, 0), null, false],
            ['Faculty general assembly', 'meeting', now()->addDays(21)->setTime(13, 30), 'University Gymnasium', true],
        ];

        foreach ($events as [$title, $type, $start, $venue, $everyone]) {
            CalendarEvent::query()->create([
                'title' => $title,
                'type' => $type,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addHours(2),
                'venue' => $venue,
                'for_everyone' => $everyone,
                'status' => 'scheduled',
                'created_by' => $creator?->id,
            ]);
        }
    }
}
