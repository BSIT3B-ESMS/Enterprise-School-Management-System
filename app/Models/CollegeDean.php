<?php

namespace App\Models;

use Database\Factories\CollegeDeanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Who is dean of a college. Being dean is an assignment on an employee, not a role: a dean keeps the Employee role and
 * gets read-only access to their own college.
 */
#[Fillable(['college_id', 'employee_id', 'started_on', 'ended_on'])]
class CollegeDean extends Model
{
    /** @use HasFactory<CollegeDeanFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    /**
     * @param  Builder<CollegeDean>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->whereNull('ended_on');
    }

    /**
     * @return BelongsTo<College, $this>
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
