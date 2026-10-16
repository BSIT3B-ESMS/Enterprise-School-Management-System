<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'slots', 'is_open'])]
class Program extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'slots' => 'integer',
            'is_open' => 'boolean',
        ];
    }

    public function choices(): HasMany
    {
        return $this->hasMany(ApplicationProgramChoice::class);
    }

    /** Released passes whose first choice is this program (slots taken). */
    public function filledSlots(): int
    {
        return Application::query()
            ->where('status', 'passed')
            ->whereHas('result', fn ($q) => $q->where('is_released', true))
            ->whereHas('choices', fn ($q) => $q->where('program_id', $this->id)->where('rank', 1))
            ->count();
    }
}
