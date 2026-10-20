<?php

namespace App\Services;

use App\Enums\ContractTerm;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * PLP's academic year runs August to March: the 1st semester from August to the first week of November, the 2nd semester
 * from December to March. The month and day of each boundary are in config/ems.php, so HR can change them without code.
 */
class AcademicCalendar
{
    /**
     * The first and last day of a contract term in the academic year that starts in $startYear (2026 means 2026-2027).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function dates(ContractTerm $term, int $startYear): array
    {
        $first = config('ems.academic_calendar.first_semester');
        $second = config('ems.academic_calendar.second_semester');

        return match ($term) {
            ContractTerm::FirstSemester => [$this->on($first['start'], $startYear), $this->on($first['end'], $startYear)],
            ContractTerm::SecondSemester => [$this->on($second['start'], $startYear), $this->on($second['end'], $startYear + 1)],
            ContractTerm::AcademicYear => [$this->on($first['start'], $startYear), $this->on($second['end'], $startYear + 1)],
        };
    }

    public function endOf(ContractTerm $term, int $startYear): CarbonImmutable
    {
        return $this->dates($term, $startYear)[1];
    }

    /**
     * The year an academic year started in, for a date: August and later belong to the year that starts that year, earlier
     * months to the year before.
     */
    public function startYearOf(CarbonInterface $date): int
    {
        $firstStartMonth = (int) explode('-', config('ems.academic_calendar.first_semester.start'))[0];

        return $date->month >= $firstStartMonth ? $date->year : $date->year - 1;
    }

    /**
     * The term and academic year whose last day is the given date, or null when it is not the end of a term.
     *
     * @return array{term: ContractTerm, year: int}|null
     */
    public function termFor(CarbonInterface $endDate): ?array
    {
        foreach ([$endDate->year - 1, $endDate->year] as $startYear) {
            foreach (ContractTerm::cases() as $term) {
                if ($this->endOf($term, $startYear)->isSameDay($endDate)) {
                    return ['term' => $term, 'year' => $startYear];
                }
            }
        }

        return null;
    }

    public function yearLabel(int $startYear): string
    {
        return $startYear.'-'.($startYear + 1);
    }

    /**
     * The semester or academic year a contract end date closes, for example "1st Semester, AY 2026-2027", or null when the
     * date is not the end of a term.
     */
    public function labelForEnd(CarbonInterface $endDate): ?string
    {
        foreach ([$endDate->year - 1, $endDate->year] as $startYear) {
            foreach (ContractTerm::cases() as $term) {
                if ($this->endOf($term, $startYear)->isSameDay($endDate)) {
                    return $term->label().', AY '.$this->yearLabel($startYear);
                }
            }
        }

        return null;
    }

    /**
     * The academic years offered when HR opens a part-time post: the current one and the next.
     *
     * @return array<int, string>
     */
    public function selectableYears(): array
    {
        $current = $this->startYearOf(now());

        return [$current => $this->yearLabel($current), $current + 1 => $this->yearLabel($current + 1)];
    }

    private function on(string $monthDay, int $year): CarbonImmutable
    {
        [$month, $day] = array_map('intval', explode('-', $monthDay));

        return CarbonImmutable::create($year, $month, $day)->startOfDay();
    }
}
