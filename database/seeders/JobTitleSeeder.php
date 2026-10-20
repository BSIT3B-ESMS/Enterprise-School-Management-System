<?php

namespace Database\Seeders;

use App\Enums\JobTitleCategory;
use App\Models\College;
use App\Models\JobTitle;
use App\Models\Office;
use Illuminate\Database\Seeder;

class JobTitleSeeder extends Seeder
{
    /**
     * Faculty titles every college has, with their salary grade from the government plantilla
     * (governmentph.com/salary-grade-table-government-plantilla-positions).
     *
     * @var array<string, int>
     */
    public const FACULTY_TITLES = [
        'Instructor I' => 12, 'Instructor II' => 13, 'Instructor III' => 14,
        'Assistant Professor I' => 15, 'Assistant Professor II' => 16, 'Assistant Professor III' => 17, 'Assistant Professor IV' => 18,
        'Associate Professor I' => 19, 'Associate Professor II' => 20, 'Associate Professor III' => 21,
        'Associate Professor IV' => 22, 'Associate Professor V' => 23,
        'Professor I' => 24, 'Professor II' => 25, 'Professor III' => 26, 'Professor IV' => 27, 'Professor V' => 28, 'Professor VI' => 29,
        // The plantilla page has no College Dean. SG 24 is its Vocational School Dean. VERIFY with PLP HR.
        'Dean' => 24,
    ];

    /**
     * Non-teaching titles every college has. The plantilla page has no College Secretary; SG 11 is a placeholder.
     * VERIFY with PLP HR.
     *
     * @var array<string, int>
     */
    public const COLLEGE_TITLES = ['College Secretary' => 11];

    /**
     * Non-teaching titles by office code, using plantilla titles and grades.
     *
     * @var array<string, array<string, int>>
     */
    public const OFFICE_TITLES = [
        'HR' => ['Human Resource Management Officer I' => 11, 'Human Resource Management Assistant' => 8],
        'MIS' => ['Information Technology Officer I' => 19, 'Administrative Assistant II' => 8],
        'FIN' => ['Accountant I' => 11, 'Accounting Clerk I' => 4],
        'SSO' => ['Administrative Officer I' => 10, 'Administrative Assistant I' => 7, 'Administrative Aide I' => 1],
    ];

    public function run(): void
    {
        foreach (College::query()->get() as $college) {
            foreach (self::FACULTY_TITLES as $title => $salaryGrade) {
                JobTitle::query()->updateOrCreate(
                    ['title' => $title, 'college_id' => $college->id],
                    ['category' => JobTitleCategory::Faculty, 'salary_grade' => $salaryGrade],
                );
            }

            foreach (self::COLLEGE_TITLES as $title => $salaryGrade) {
                JobTitle::query()->updateOrCreate(
                    ['title' => $title, 'college_id' => $college->id],
                    ['category' => JobTitleCategory::NonTeaching, 'salary_grade' => $salaryGrade],
                );
            }
        }

        foreach (self::OFFICE_TITLES as $officeCode => $titles) {
            $office = Office::query()->where('code', $officeCode)->firstOrFail();

            foreach ($titles as $title => $salaryGrade) {
                JobTitle::query()->updateOrCreate(
                    ['title' => $title, 'office_id' => $office->id],
                    ['category' => JobTitleCategory::NonTeaching, 'salary_grade' => $salaryGrade],
                );
            }
        }
    }
}
