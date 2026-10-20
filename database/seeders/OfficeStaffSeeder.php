<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Models\College;
use App\Models\JobTitle;
use App\Models\Office;

/**
 * Seven fictional non-teaching staff, one or two per office, so the office side of the system has people. Unlike the
 * faculty roster these names are invented. The two HR Admins used for local development are here too, so their accounts
 * have full-time employee records and full-time leave.
 */
class OfficeStaffSeeder
{
    /**
     * First name, surname, office code, job title, and the college they also teach in (if any).
     *
     * @var list<array{first: string, last: string, office: string, title: string, teachesIn: string|null}>
     */
    private const STAFF = [
        ['first' => 'Lourdes', 'last' => 'Manansala', 'office' => 'HR', 'title' => 'Human Resource Management Officer I', 'teachesIn' => null],
        ['first' => 'Benedict', 'last' => 'Villafuerte', 'office' => 'MIS', 'title' => 'Information Technology Officer I', 'teachesIn' => null],
        ['first' => 'Corazon', 'last' => 'Ilagan', 'office' => 'FIN', 'title' => 'Accountant I', 'teachesIn' => 'CBA'],
        ['first' => 'Emmanuel', 'last' => 'Dumalagan', 'office' => 'SSO', 'title' => 'Administrative Officer I', 'teachesIn' => null],
        ['first' => 'Perla', 'last' => 'Bagtas', 'office' => 'SSO', 'title' => 'Administrative Assistant I', 'teachesIn' => null],
        ['first' => 'Niccolo Franco', 'last' => 'Yumul', 'office' => 'HR', 'title' => 'Human Resource Management Officer I', 'teachesIn' => null],
        ['first' => 'Prespurt James', 'last' => 'Bellen', 'office' => 'HR', 'title' => 'Human Resource Management Assistant', 'teachesIn' => null],
    ];

    /**
     * @return list<array{first: string, middle: null, last: string, suffix: null, college: ?College, office: Office, jobTitle: JobTitle, type: EmploymentType, kind: string, isDean: bool}>
     */
    public static function specs(): array
    {
        return array_map(function (array $row): array {
            $office = Office::query()->where('code', $row['office'])->firstOrFail();

            return [
                'first' => $row['first'],
                'middle' => null,
                'last' => $row['last'],
                'suffix' => null,
                'college' => $row['teachesIn'] ? College::query()->where('code', $row['teachesIn'])->firstOrFail() : null,
                'office' => $office,
                'jobTitle' => JobTitle::query()->where('office_id', $office->id)->where('title', $row['title'])->firstOrFail(),
                'type' => EmploymentType::FullTime,
                'kind' => 'staff',
                'isDean' => false,
            ];
        }, self::STAFF);
    }
}
