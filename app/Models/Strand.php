<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** An SHS strand on the applicant form and the programs suggested for it (FR-06, FR-26). */
#[Fillable(['name', 'sort_order', 'is_active'])]
class Strand extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'strand_program');
    }

    /** Names shown on the form, in order. */
    public static function activeNames(): array
    {
        return self::where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name')->all();
    }
}
