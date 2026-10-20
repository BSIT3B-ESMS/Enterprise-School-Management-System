<?php

namespace Database\Seeders;

use App\Models\RateTier;
use Illuminate\Database\Seeder;

class RateTierSeeder extends Seeder
{
    /**
     * One rate tier per band and step (see PayBands), effective 2026-01-01. They are reference values only; nothing in
     * the EMS computes pay.
     */
    public function run(): void
    {
        foreach (PayBands::bands() as $band) {
            for ($step = 1; $step <= PayBands::STEPS; $step++) {
                RateTier::query()->updateOrCreate(
                    ['salary_grade' => $band['grade'], 'step' => $step, 'effective_date' => '2026-01-01'],
                    ['monthly_amount' => PayBands::amountAt($band, $step)],
                );
            }
        }
    }
}
