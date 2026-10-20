<?php

namespace Database\Seeders;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\MaritalStatus;
use App\Enums\Sex;
use App\Models\College;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Office;
use Illuminate\Database\Seeder;

/**
 * Five fictional non-teaching staff, one or two per office, so the office side of the system has data. Unlike the
 * faculty roster these people are invented, with full synthetic details.
 */
class OfficeStaffSeeder extends Seeder
{
    /**
     * Office code, job title, and the college they also teach in (if any).
     *
     * @var list<array{office: string, title: string, teachesIn: string|null}>
     */
    private const STAFF = [
        ['office' => 'HR', 'title' => 'HR Management Officer I', 'teachesIn' => null],
        ['office' => 'MIS', 'title' => 'IT Officer I', 'teachesIn' => null],
        ['office' => 'FIN', 'title' => 'Accountant I', 'teachesIn' => 'CBA'],
        ['office' => 'SSO', 'title' => 'Administrative Officer I', 'teachesIn' => null],
        ['office' => 'SSO', 'title' => 'Administrative Assistant I', 'teachesIn' => null],
    ];

    private const FIRST_NAMES = ['Lourdes', 'Benedict', 'Corazon', 'Emmanuel', 'Perla'];

    private const LAST_NAMES = ['Manansala', 'Villafuerte', 'Ilagan', 'Dumalagan', 'Bagtas'];

    public function run(): void
    {
        fake()->seed(20261021);

        foreach (self::STAFF as $index => $row) {
            $office = Office::query()->where('code', $row['office'])->firstOrFail();
            $title = JobTitle::query()->where('office_id', $office->id)->where('title', $row['title'])->firstOrFail();
            $first = self::FIRST_NAMES[$index];
            $last = self::LAST_NAMES[$index];

            $employee = new Employee;
            $employee->forceFill([
                'employee_no' => Employee::nextEmployeeNo(),
                'last_name' => $last,
                'first_name' => $first,
                'middle_name' => null,
                'birthdate' => fake()->dateTimeBetween('1970-01-01', '1998-12-31')->format('Y-m-d'),
                'sex' => fake()->randomElement(Sex::cases()),
                'marital_status' => fake()->randomElement([MaritalStatus::Single, MaritalStatus::Married]),
                'employment_type' => EmploymentType::FullTime,
                'hired_on' => fake()->dateTimeBetween('2012-06-01', '2025-08-31')->format('Y-m-d'),
                'college_id' => $row['teachesIn'] ? College::query()->where('code', $row['teachesIn'])->value('id') : null,
                'office_id' => $office->id,
                'job_title_id' => $title->id,
                'salary_step' => fake()->numberBetween(1, 3),
                'official_email' => Employee::officialEmailFor($last, $first),
                'personal_email' => strtolower($first.'.'.$last).'@example.test',
                'phone' => '09'.fake()->numerify('#########'),
                'address' => fake()->numberBetween(10, 400).' Shaw Boulevard, Pasig City',
                'status' => EmployeeStatus::Active,
            ]);
            $employee->setGovernmentIds([
                'tin' => fake()->numerify('###-###-###-000'),
                'sss' => fake()->numerify('##-#######-#'),
                'philhealth' => fake()->numerify('##-#########-#'),
                'pagibig' => fake()->numerify('####-####-####'),
                'gsis' => fake()->numerify('###########'),
            ]);
            $employee->save();

            $employee->emergencyContacts()->create([
                'name' => fake()->name(),
                'relationship' => fake()->randomElement(['Spouse', 'Parent', 'Sibling']),
                'phone' => '09'.fake()->numerify('#########'),
                'address' => $employee->address,
                'is_primary' => true,
            ]);
        }
    }
}
