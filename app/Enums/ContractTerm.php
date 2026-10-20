<?php

namespace App\Enums;

/**
 * How long a part-time contract runs. PLP has two semesters a year, so a part-time post ends with a semester or with the
 * academic year. The dates come from App\Services\AcademicCalendar.
 */
enum ContractTerm: string
{
    case FirstSemester = 'first_semester';
    case SecondSemester = 'second_semester';
    case AcademicYear = 'academic_year';

    public function label(): string
    {
        return match ($this) {
            self::FirstSemester => '1st Semester',
            self::SecondSemester => '2nd Semester',
            self::AcademicYear => 'Whole Academic Year',
        };
    }
}
