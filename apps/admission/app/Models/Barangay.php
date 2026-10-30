<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** A Pasig barangay in district 1 or 2, maintained by staff (FR-03, FR-26). */
#[Fillable(['district', 'name', 'is_active'])]
class Barangay extends Model
{
    public const DISTRICTS = [1, 2];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'district' => 'integer'];
    }

    /** @return array<int, list<string>> district => names, as the form's data-barangays expects */
    public static function byDistrict(): array
    {
        $list = array_fill_keys(self::DISTRICTS, []);
        foreach (self::where('is_active', true)->orderBy('name')->get(['district', 'name']) as $b) {
            $list[$b->district][] = $b->name;
        }

        return $list;
    }
}
