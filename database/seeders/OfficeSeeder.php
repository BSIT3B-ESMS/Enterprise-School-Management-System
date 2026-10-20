<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    /**
     * PLP's non-teaching offices.
     *
     * @var array<string, string>
     */
    private const OFFICES = [
        'HR' => 'Human Resources',
        'MIS' => 'Management Information System',
        'FIN' => 'Finance & Accounting',
        'SSO' => 'Student Success Office',
    ];

    public function run(): void
    {
        foreach (self::OFFICES as $code => $name) {
            Office::query()->updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }
    }
}
