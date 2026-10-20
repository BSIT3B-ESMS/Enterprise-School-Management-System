<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Models\College;
use App\Models\JobTitle;
use Illuminate\Database\Seeder;

/**
 * Seven open job positions, one Instructor I opening in each college, published for the Careers website.
 */
class JobPostingSeeder extends Seeder
{
    private const CAMPUS = '12-B Alcalde Jose, Pasig City';

    public function run(): void
    {
        foreach (College::query()->orderBy('code')->get() as $college) {
            JobTitle::query()
                ->where('college_id', $college->id)
                ->where('title', 'Instructor I')
                ->update([
                    'openings' => 1,
                    'is_published' => true,
                    'employment_type' => EmploymentType::FullTime->value,
                    'location' => self::CAMPUS,
                    'summary' => "Teaches courses in the {$college->name} and supports its students.",
                    'description' => "Teaches undergraduate courses of the {$college->name}, prepares course materials, and assesses student learning.",
                    'responsibilities' => [
                        'Teach assigned courses and keep class records',
                        'Prepare syllabi, lesson plans, and assessments',
                        'Advise students and take part in college activities',
                    ],
                    'qualifications' => [
                        'Master’s degree in a relevant field (or in progress, for fresh graduates)',
                        'Licensure where the program requires it',
                        'Good communication skills',
                    ],
                    'requirements' => [
                        'Application letter',
                        'Curriculum vitae (CV)',
                        'Transcript of records',
                        'Two valid government-issued IDs',
                    ],
                ]);
        }
    }
}
