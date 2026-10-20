<?php

namespace App\Models;

use Database\Factories\CollegeTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An append-only record of an assignment change (college, office, or job title). Rows are never edited after they are
 * written, except that `applied_at` is set once when a future-dated transfer takes effect.
 */
#[Fillable([
    'employee_id', 'from_college_id', 'to_college_id', 'from_office_id', 'to_office_id',
    'from_job_title_id', 'to_job_title_id', 'effective_date', 'reason', 'recorded_by', 'applied_at',
])]
class CollegeTransfer extends Model
{
    /** @use HasFactory<CollegeTransferFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'applied_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<College, $this>
     */
    public function fromCollege(): BelongsTo
    {
        return $this->belongsTo(College::class, 'from_college_id');
    }

    /**
     * @return BelongsTo<College, $this>
     */
    public function toCollege(): BelongsTo
    {
        return $this->belongsTo(College::class, 'to_college_id');
    }

    /**
     * @return BelongsTo<JobTitle, $this>
     */
    public function fromJobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'from_job_title_id');
    }

    /**
     * @return BelongsTo<JobTitle, $this>
     */
    public function toJobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'to_job_title_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
