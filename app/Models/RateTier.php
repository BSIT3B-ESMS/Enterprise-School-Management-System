<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\RateTierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A reference monthly base rate for a salary grade and step. A rate change adds a new row with a later effective date;
 * older rows are kept. The amount is reference information only: nothing here computes pay.
 */
#[Fillable(['salary_grade', 'step', 'monthly_amount', 'effective_date'])]
class RateTier extends Model
{
    /** @use HasFactory<RateTierFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_amount' => 'decimal:2',
            'effective_date' => 'date',
        ];
    }

    /**
     * The whole schedule in force on a date (today by default): for each salary grade, the amount at each step, using
     * the newest row that is already effective for that grade and step.
     *
     * @return array<int, array<int, string>>
     */
    public static function scheduleOn(?CarbonInterface $on = null): array
    {
        $schedule = [];

        static::query()
            ->whereDate('effective_date', '<=', ($on ?? now())->toDateString())
            ->orderByDesc('effective_date')
            ->get()
            ->each(function (self $tier) use (&$schedule): void {
                $schedule[$tier->salary_grade][$tier->step] ??= $tier->monthly_amount;
            });

        ksort($schedule);

        foreach ($schedule as &$steps) {
            ksort($steps);
        }

        unset($steps);

        return $schedule;
    }

    /**
     * The rate in force on a date (today by default) for a grade and step: the newest row that is already effective.
     */
    public static function currentFor(int $salaryGrade, int $step, ?CarbonInterface $on = null): ?self
    {
        return static::query()
            ->where('salary_grade', $salaryGrade)
            ->where('step', $step)
            ->whereDate('effective_date', '<=', ($on ?? now())->toDateString())
            ->orderByDesc('effective_date')
            ->first();
    }
}
