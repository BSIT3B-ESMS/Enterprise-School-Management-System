<?php

namespace App\Enums;

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
