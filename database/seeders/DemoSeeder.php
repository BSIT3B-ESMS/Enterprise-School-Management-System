<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\AdmissionPeriod;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local/testing only: staff logins and exam sessions so the panels have
 * something to show. Password for every demo account: "password123".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            ['Maria Santos', 'admissions@plpasig.edu.ph', Role::Admission],
            ['R. Dizon', 'r.dizon@plpasig.edu.ph', Role::Admission],
            ['Dr. L. Bautista', 'clinic@plpasig.edu.ph', Role::Clinic],
        ];
        foreach ($staff as [$name, $email, $role]) {
            $user = User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => 'password123']);
            $user->syncRoles([$role->value]);
        }

        $period = AdmissionPeriod::current();
        $rooms = ['Main Building · Room 301', 'Main Building · Room 302', 'Annex · AVR'];
        foreach (['2026-12-05', '2026-12-06', '2026-12-12', '2026-12-13'] as $i => $date) {
            foreach (['A', 'B'] as $j => $batch) {
                ExamSession::firstOrCreate(
                    ['date' => $date, 'batch' => $batch, 'venue' => $rooms[($i + $j) % count($rooms)]],
                    ['admission_period_id' => $period->id, 'capacity' => 30],
                );
            }
        }
    }
}
