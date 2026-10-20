<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\LeaveType;
use App\Models\LeavePolicy;
use Illuminate\Database\Seeder;

class LeavePolicySeeder extends Seeder
{
    /**
     * Yearly leave credits for full-time (permanent, plantilla) staff under Philippine law. PLP is a local government
     * university, so the Civil Service leave rules apply:
     *
     * - Vacation and Sick Leave: 15 days each a year (1.25 days a month), CSC Omnibus Rules on Leave.
     * - Special Privilege Leave: 3 days a year.
     * - Special Emergency (Calamity) Leave: 5 days, CSC MC No. 2, s. 2012.
     * - Maternity: 105 days (RA 11210). Paternity: 7 days (RA 8187). Solo Parent: 7 days (RA 8972 as amended).
     * - Special Leave Benefits for Women: up to 60 days (RA 9710). VAWC leave: 10 days (RA 9262).
     *
     * The statutes give the same credits at every rank, so these are general rows (no job title). To give a title more
     * (for example by a PLP ordinance), add a row with its job_title_id; it overrides the general row. Part-time
     * faculty are paid by the hour and earn no leave credits. VERIFY against PLP's own personnel policy.
     *
     * @var array<string, float>
     */
    private const FULL_TIME_DAYS = [
        'vacation' => 15,
        'sick' => 15,
        'special_privilege' => 3,
        'emergency' => 5,
        'maternity' => 105,
        'paternity' => 7,
        'solo_parent' => 7,
        'special_women' => 60,
        'vawc' => 10,
    ];

    public function run(): void
    {
        foreach (self::FULL_TIME_DAYS as $leaveType => $days) {
            LeavePolicy::query()->updateOrCreate(
                ['leave_type' => LeaveType::from($leaveType), 'employment_type' => EmploymentType::FullTime, 'job_title_id' => null],
                ['days_per_year' => $days, 'is_active' => true],
            );
        }
    }
}
