<?php

namespace Database\Seeders;

use App\Models\RateTier;
use Illuminate\Database\Seeder;

class RateTierSeeder extends Seeder
{
    /**
     * The 2026 monthly basic salary of each salary grade, Step 1 (the third tranche of the government salary schedule,
     * EO 64, effective January 1, 2026), from governmentph.com/salary-grade-2026. Only Step 1 is used. They are reference
     * values only; nothing in the EMS computes pay. VERIFY against the DBM circular before real use.
     *
     * @var array<int, int>
     */
    public const STEP_ONE_2026 = [
        1 => 14634, 2 => 15522, 3 => 16486, 4 => 17506, 5 => 18581, 6 => 19716, 7 => 20914, 8 => 22423, 9 => 24329,
        10 => 26917, 11 => 31705, 12 => 33947, 13 => 36125, 14 => 38764, 15 => 42178, 16 => 45694, 17 => 49562,
        18 => 53818, 19 => 59153, 20 => 66052, 21 => 73303, 22 => 81796, 23 => 91306, 24 => 102603, 25 => 116643,
        26 => 131807, 27 => 148940, 28 => 167129, 29 => 187531, 30 => 210718, 31 => 300961, 32 => 356237, 33 => 449157,
    ];

    public function run(): void
    {
        foreach (self::STEP_ONE_2026 as $salaryGrade => $amount) {
            RateTier::query()->updateOrCreate(
                ['salary_grade' => $salaryGrade, 'effective_date' => '2026-01-01'],
                ['monthly_amount' => $amount],
            );
        }
    }
}
