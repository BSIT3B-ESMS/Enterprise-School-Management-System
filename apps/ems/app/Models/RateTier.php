<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\RateTierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The reference monthly base pay of a salary grade (Step 1 of the government salary schedule). A change adds a new row
 * with a later effective date; older rows are kept. The amount is reference information only: nothing here computes pay.
 */
#[Fillable(['salary_grade', 'monthly_amount', 'effective_date'])]
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
     * The whole schedule in force on a date (today by default): the monthly pay of each salary grade, using the newest
     * row that is already effective for that grade.
     *
     * @return array<int, string>
     */
    public static function scheduleOn(?CarbonInterface $on = null): array
    {
        $schedule = [];

        static::query()
            ->whereDate('effective_date', '<=', ($on ?? now())->toDateString())
            ->orderByDesc('effective_date')
            ->get()
            ->each(function (self $tier) use (&$schedule): void {
                $schedule[$tier->salary_grade] ??= $tier->monthly_amount;
            });

        ksort($schedule);

        return $schedule;
    }

    /**
     * The rate in force on a date (today by default) for a salary grade: the newest row that is already effective.
     */
    public static function currentFor(int $salaryGrade, ?CarbonInterface $on = null): ?self
    {
        return static::query()
            ->where('salary_grade', $salaryGrade)
            ->whereDate('effective_date', '<=', ($on ?? now())->toDateString())
            ->orderByDesc('effective_date')
            ->first();
    }
}
