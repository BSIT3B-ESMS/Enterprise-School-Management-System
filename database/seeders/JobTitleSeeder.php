<?php

namespace Database\Seeders;

use App\Enums\JobTitleCategory;
use App\Models\College;
use App\Models\JobTitle;
use App\Models\Office;
use Illuminate\Database\Seeder;

class JobTitleSeeder extends Seeder
{
    public function run(): void
    {
        $bands = PayBands::bands();

        foreach (College::query()->get() as $college) {
            foreach (PayBands::FACULTY_RANKS as $rank => [$levels]) {
                for ($level = 1; $level <= $levels; $level++) {
                    $title = $rank.' '.['', 'I', 'II', 'III', 'IV', 'V', 'VI'][$level];

                    JobTitle::query()->updateOrCreate(
                        ['title' => $title, 'college_id' => $college->id],
                        ['category' => JobTitleCategory::Faculty, 'salary_grade' => $bands[$title]['grade']],
                    );
                }
            }
        }

        foreach (College::query()->get() as $college) {
            foreach (array_keys(PayBands::COLLEGE_TITLES) as $title) {
                JobTitle::query()->updateOrCreate(
                    ['title' => $title, 'college_id' => $college->id],
                    ['category' => JobTitleCategory::NonTeaching, 'salary_grade' => $bands[$title]['grade']],
                );
            }
        }

        foreach (PayBands::OFFICE_TITLES as $officeCode => $titles) {
            $office = Office::query()->where('code', $officeCode)->firstOrFail();

            foreach (array_keys($titles) as $title) {
                JobTitle::query()->updateOrCreate(
                    ['title' => $title, 'office_id' => $office->id],
                    ['category' => JobTitleCategory::NonTeaching, 'salary_grade' => $bands[$title]['grade']],
                );
            }
        }
    }
}
