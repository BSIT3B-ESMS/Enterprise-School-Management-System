<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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
        return static::filledCounts()[$this->id] ?? 0;
    }

    /** Slots taken for every program in one query: [program_id => count]. */
    public static function filledCounts(): array
    {
        return DB::table('application_program_choices as c')
            ->join('applications as a', 'a.id', '=', 'c.application_id')
            ->join('exam_results as r', 'r.application_id', '=', 'a.id')
            ->where('c.rank', 1)->where('a.status', 'passed')->where('r.is_released', true)->whereNull('a.deleted_at')
            ->groupBy('c.program_id')
            ->pluck(DB::raw('count(*)'), 'c.program_id')
            ->map(fn ($n) => (int) $n)
            ->all();
    }
}
