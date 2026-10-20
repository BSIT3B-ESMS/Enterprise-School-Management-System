<?php

namespace App\Enums;

enum Role: string
{
    case HrHead = 'hr_head';
    case HrStaff = 'hr_staff';
    case Employee = 'employee';

    public function isHr(): bool
    {
        return $this === self::HrHead || $this === self::HrStaff;
    }
}
