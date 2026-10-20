<?php

namespace App\Models;

use App\Enums\JobTitleCategory;
use Database\Factories\JobTitleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A position that belongs to exactly one college (faculty ranks) or one office (non-teaching), with the salary grade
 * that decides its base rate.
 */
#[Fillable([
    'title', 'category', 'college_id', 'office_id', 'salary_grade', 'is_active', 'openings', 'is_published',
    'employment_type', 'location', 'summary', 'description', 'responsibilities', 'qualifications', 'requirements',
])]
class JobTitle extends Model
{
    /** @use HasFactory<JobTitleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => JobTitleCategory::class,
            'is_active' => 'boolean',
            'is_published' => 'boolean',
            'responsibilities' => 'array',
            'qualifications' => 'array',
            'requirements' => 'array',
        ];
    }

    /**
     * @return BelongsTo<College, $this>
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return HasMany<LeavePolicy, $this>
     */
    public function leavePolicies(): HasMany
    {
        return $this->hasMany(LeavePolicy::class);
    }
}
