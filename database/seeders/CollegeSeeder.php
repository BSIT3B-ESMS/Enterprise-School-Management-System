<?php

namespace Database\Seeders;

use App\Models\College;
use Illuminate\Database\Seeder;

class CollegeSeeder extends Seeder
{
    /**
     * PLP's seven colleges and their programs. The programs are the ones the owner listed; more are added later.
     *
     * @var array<string, array{name: string, programs: array<string, string>}>
     */
    private const COLLEGES = [
        'CCS' => [
            'name' => 'College of Computer Studies',
            'programs' => [
                'BSCS' => 'Bachelor of Science in Computer Science',
                'BSIT' => 'Bachelor of Science in Information Technology',
            ],
        ],
        'COE' => [
            'name' => 'College of Engineering',
            'programs' => ['BSECE' => 'Bachelor of Science in Electronics Engineering'],
        ],
        'CON' => [
            'name' => 'College of Nursing',
            'programs' => ['BSN' => 'Bachelor of Science in Nursing'],
        ],
        'CIHM' => [
            'name' => 'College of International Hospitality Management',
            'programs' => ['BSHM' => 'Bachelor of Science in Hospitality Management'],
        ],
        'CAS' => [
            'name' => 'College of Arts and Sciences',
            'programs' => ['AB Psy' => 'Bachelor of Arts in Psychology'],
        ],
        'COED' => [
            'name' => 'College of Education',
            'programs' => [
                'BEEd' => 'Bachelor of Elementary Education',
                'BSED English' => 'Bachelor of Science in Secondary Education Major in English',
                'BSED Filipino' => 'Bachelor of Science in Secondary Education Major in Filipino',
                'BSED Math' => 'Bachelor of Science in Secondary Education Major in Mathematics',
            ],
        ],
        'CBA' => [
            'name' => 'College of Business and Accountancy',
            'programs' => [
                'BSA' => 'Bachelor of Science in Accountancy',
                'BSBA' => 'Bachelor of Science in Business Administration Major in Marketing Management',
                'BS Entrep' => 'Bachelor of Science in Entrepreneurship',
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::COLLEGES as $code => $details) {
            $college = College::query()->updateOrCreate(['code' => $code], ['name' => $details['name'], 'is_active' => true]);

            foreach ($details['programs'] as $programCode => $programName) {
                $college->programs()->updateOrCreate(['code' => $programCode], ['name' => $programName, 'is_active' => true]);
            }
        }
    }
}
