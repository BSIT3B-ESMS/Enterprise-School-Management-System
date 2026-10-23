<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\Program;
use App\Models\Strand;
use Illuminate\Database\Seeder;

/**
 * Starting reference lists staff maintain afterwards (FR-06, FR-26): SHS
 * strands with their suggested programs, and Pasig barangays by district.
 * Safe to run again: it adds what is missing and never undoes staff edits
 * (a strand's suggestions are only filled while it has none).
 */
class ReferenceListSeeder extends Seeder
{
    public const STRANDS = [
        'STEM' => ['BSCS', 'BSIT', 'BSECE', 'BSMATH', 'BSN'],
        'ABM' => ['BSA', 'BSBA-ENT', 'BSBA-MM', 'BSHM'],
        'HUMSS' => ['ABPSY', 'BSED-ENG', 'BSED-FIL', 'BEED'],
        'GAS' => ['BSBA-ENT', 'BEED', 'ABPSY'],
        'TVL – ICT' => ['BSIT', 'BSCS'],
        'TVL – Other' => ['BSHM', 'BSBA-ENT'],
        'Arts and Design' => ['ABPSY', 'BSIT'],
        'Sports' => ['BSED-ENG', 'BEED'],
        'ALS / Not applicable' => [],
    ];

    public const BARANGAYS = [
        1 => ['Bagong Ilog', 'Bagong Katipunan', 'Bambang', 'Buting', 'Caniogan', 'Kalawaan', 'Kapasigan', 'Kapitolyo', 'Malinao', 'Oranbo', 'Palatiw', 'Pineda', 'Sagad', 'San Antonio', 'San Joaquin', 'San Jose', 'San Nicolas', 'Santa Cruz', 'Santa Rosa', 'Santo Tomas', 'Sumilang', 'Ugong'],
        2 => ['Dela Paz', 'Manggahan', 'Maybunga', 'Pinagbuhatan', 'Rosario', 'San Miguel', 'Santa Lucia', 'Santolan'],
    ];

    public function run(): void
    {
        $order = 0;
        foreach (self::STRANDS as $name => $codes) {
            $strand = Strand::firstOrCreate(['name' => $name], ['sort_order' => ++$order]);
            if (! $strand->programs()->exists()) {
                $strand->programs()->sync(Program::whereIn('code', $codes)->pluck('id'));
            }
        }
        foreach (self::BARANGAYS as $district => $names) {
            foreach ($names as $name) {
                Barangay::firstOrCreate(['district' => $district, 'name' => $name]);
            }
        }
    }
}
