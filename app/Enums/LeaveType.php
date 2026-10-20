<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum LeaveType: string
{
    case Vacation = 'vacation';
    case Sick = 'sick';
    case SpecialPrivilege = 'special_privilege';
    case Emergency = 'emergency';
    case Maternity = 'maternity';
    case Paternity = 'paternity';
    case SoloParent = 'solo_parent';
    case SpecialWomen = 'special_women';
    case Vawc = 'vawc';

    public function label(): string
    {
        return match ($this) {
            self::Vacation => 'Vacation Leave',
            self::Sick => 'Sick Leave',
            self::SpecialPrivilege => 'Special Privilege Leave',
            self::Emergency => 'Special Emergency (Calamity) Leave',
            self::Maternity => 'Maternity Leave',
            self::Paternity => 'Paternity Leave',
            self::SoloParent => 'Solo Parent Leave',
            self::SpecialWomen => 'Special Leave Benefits for Women',
            self::Vawc => 'Leave for Victims of Violence Against Women and Children',
        };
    }

    /**
     * Maternity (105 days) and the women's special leave (up to two months) are counted in calendar days; paternity (7
     * working days) and the other leaves count working days, Monday to Friday. Holidays are not excluded. VERIFY with PLP HR.
     */
    public function usesCalendarDays(): bool
    {
        return in_array($this, [self::Maternity, self::SpecialWomen], true);
    }

    /**
     * Sick and emergency leave are often filed after the fact, so they may start up to 30 days in the past.
     */
    public function allowsBackdating(): bool
    {
        return in_array($this, [self::Sick, self::Emergency], true);
    }

    /**
     * The days a leave from one date to another uses up, both dates included.
     */
    public function daysBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        if ($this->usesCalendarDays()) {
            return (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        }

        $days = 0;

        for ($day = $start->copy()->startOfDay(); $day->lte($end); $day->addDay()) {
            $days += $day->isWeekday() ? 1 : 0;
        }

        return $days;
    }

    /**
     * Whether the law makes this leave available to a person with this sex and civil status. Maternity (RA 11210),
     * Special Leave Benefits for Women (RA 9710), and VAWC leave (RA 9262) are for women; Paternity (RA 8187) is for
     * married men. The rest are open to everyone.
     */
    public function isAvailableTo(?Sex $sex, ?MaritalStatus $maritalStatus): bool
    {
        return match ($this) {
            self::Maternity, self::SpecialWomen, self::Vawc => $sex === Sex::Female,
            self::Paternity => $sex === Sex::Male && $maritalStatus === MaritalStatus::Married,
            default => true,
        };
    }
}
