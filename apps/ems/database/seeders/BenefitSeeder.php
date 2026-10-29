<?php

namespace Database\Seeders;

use App\Models\Benefit;
use Illuminate\Database\Seeder;

/**
 * The benefits HR can put in an offer and give to an employee. They are never shown on job postings. Plain statutory and
 * common government-university benefits; VERIFY the list with PLP HR.
 */
class BenefitSeeder extends Seeder
{
    /**
     * @var array<string, array{0: string, 1: bool}>
     */
    private const BENEFITS = [
        'GSIS membership and insurance' => ['Government Service Insurance System coverage for permanent staff.', true],
        'PhilHealth coverage' => ['Health insurance through the Philippine Health Insurance Corporation.', false],
        'Pag-IBIG membership' => ['Housing fund savings and loan access through Pag-IBIG.', false],
        'Personnel Economic Relief Allowance (PERA)' => ['Monthly allowance for government personnel.', true],
        'Mid-year bonus' => ['One month of base pay, given in May.', true],
        'Year-end bonus and cash gift' => ['One month of base pay and a cash gift, given in November.', true],
        'Clothing allowance' => ['Yearly allowance for uniform or clothing.', true],
        'Professional development and training' => ['Seminars and training paid or supported by the University.', false],
        'Loyalty award' => ['Recognition for every five years of service.', true],
    ];

    public function run(): void
    {
        foreach (self::BENEFITS as $name => [$description, $fullTimeOnly]) {
            Benefit::query()->updateOrCreate(['name' => $name], ['description' => $description, 'full_time_only' => $fullTimeOnly, 'is_active' => true]);
        }
    }
}
