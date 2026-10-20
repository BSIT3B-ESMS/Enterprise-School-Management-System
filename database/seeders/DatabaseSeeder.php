<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with synthetic data only.
     */
    public function run(): void
    {
        $this->call([
            CollegeSeeder::class,
            OfficeSeeder::class,
            RateTierSeeder::class,
            JobTitleSeeder::class,
            LeavePolicySeeder::class,
            FacultyRosterSeeder::class,
            OfficeStaffSeeder::class,
            JobPostingSeeder::class,
            DevelopmentUserSeeder::class,
        ]);
    }
}
