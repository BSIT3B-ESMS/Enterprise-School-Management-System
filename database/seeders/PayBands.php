<?php

namespace Database\Seeders;

/**
 * The monthly base-pay bands PLP uses, taken from the prototype's job-title salary ranges. Each band runs from step 1
 * (the minimum) to step 8 (the maximum) in equal increments. A faculty rank's range is split evenly across its levels
 * (Instructor I to III share 36,000 to 46,000). Bands are numbered 1 upward from the lowest minimum, and that number
 * is the job title's `salary_grade`.
 *
 * VERIFY: the prototype had no range for HR Management Assistant, Accounting Clerk, Administrative Officer I, or
 * Administrative Aide, so those four are placeholders. Confirm all bands with PLP HR.
 */
final class PayBands
{
    public const STEPS = 8;

    /**
     * Faculty ranks: levels, then the minimum and maximum of the whole rank.
     *
     * @var array<string, array{int, int, int}>
     */
    public const FACULTY_RANKS = [
        'Instructor' => [3, 36000, 46000],
        'Assistant Professor' => [4, 58000, 72000],
        'Associate Professor' => [5, 72000, 90000],
        'Professor' => [6, 88000, 110000],
    ];

    /**
     * Non-teaching titles by office code, with their minimum and maximum.
     *
     * @var array<string, array<string, array{int, int}>>
     */
    public const OFFICE_TITLES = [
        'HR' => ['HR Management Officer I' => [38000, 50000], 'HR Management Assistant' => [26000, 34000]],
        'MIS' => ['IT Officer I' => [40000, 55000], 'IT Support Specialist' => [30000, 40000]],
        'FIN' => ['Accountant I' => [45000, 60000], 'Accounting Clerk' => [22000, 29000]],
        'SSO' => ['Administrative Officer I' => [34000, 44000], 'Administrative Assistant I' => [26000, 34000], 'Administrative Aide' => [18000, 24000]],
    ];

    /**
     * Non-teaching titles every college has, with their minimum and maximum (placeholders: VERIFY).
     *
     * @var array<string, array{int, int}>
     */
    public const COLLEGE_TITLES = [
        'College Secretary' => [26000, 34000],
    ];

    /**
     * Every band, keyed by job title, with its grade number.
     *
     * @return array<string, array{grade: int, min: int, max: int}>
     */
    public static function bands(): array
    {
        $bands = [];

        foreach (self::FACULTY_RANKS as $rank => [$levels, $min, $max]) {
            for ($level = 1; $level <= $levels; $level++) {
                $bands[$rank.' '.self::roman($level)] = [
                    'min' => (int) round($min + ($max - $min) * ($level - 1) / $levels),
                    'max' => (int) round($min + ($max - $min) * $level / $levels),
                ];
            }
        }

        foreach ([...self::OFFICE_TITLES, self::COLLEGE_TITLES] as $titles) {
            foreach ($titles as $title => [$min, $max]) {
                $bands[$title] = ['min' => $min, 'max' => $max];
            }
        }

        uksort($bands, fn (string $first, string $second): int => [$bands[$first]['min'], $first] <=> [$bands[$second]['min'], $second]);

        $numbered = [];
        $grade = 0;

        foreach ($bands as $title => $band) {
            $numbered[$title] = [...$band, 'grade' => ++$grade];
        }

        return $numbered;
    }

    public static function gradeFor(string $title): int
    {
        return self::bands()[$title]['grade'];
    }

    /**
     * The monthly amount at a step of a band, to the nearest peso.
     *
     * @param  array{min: int, max: int}  $band
     */
    public static function amountAt(array $band, int $step): int
    {
        return (int) round($band['min'] + ($band['max'] - $band['min']) * ($step - 1) / (self::STEPS - 1));
    }

    private static function roman(int $number): string
    {
        return ['', 'I', 'II', 'III', 'IV', 'V', 'VI'][$number];
    }
}
