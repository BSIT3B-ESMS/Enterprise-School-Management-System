<?php

namespace App\Enums;

enum Role: string
{
    case HrAdmin = 'hr_admin';
    case HrStaff = 'hr_staff';
    case Employee = 'employee';
    case Applicant = 'applicant';

    public function isHr(): bool
    {
        return $this === self::HrAdmin || $this === self::HrStaff;
    }
}
