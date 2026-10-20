<?php

namespace App\Models;

use App\Enums\ContractTerm;
use App\Enums\JobTitleCategory;
use App\Services\AcademicCalendar;
use Carbon\CarbonImmutable;
use Database\Factories\JobTitleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
    'employment_type', 'contract_term', 'academic_year', 'location', 'summary', 'description', 'responsibilities', 'qualifications', 'requirements',
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
            'contract_term' => ContractTerm::class,
            'is_active' => 'boolean',
            'is_published' => 'boolean',
            'responsibilities' => 'array',
            'qualifications' => 'array',
            'requirements' => 'array',
        ];
    }

    /**
     * The date a part-time contract for this post ends, from its term and academic year, or null for a full-time post.
     */
    public function contractEndDate(): ?CarbonImmutable
    {
        if ($this->contract_term === null || $this->academic_year === null) {
            return null;
        }

        return app(AcademicCalendar::class)->endOf($this->contract_term, $this->academic_year);
    }

    public function isOpen(): bool
    {
        return $this->is_active && $this->is_published && $this->openings > 0;
    }

    /**
     * Whether the post is open for applications: published, active, and at least one opening.
     *
     * @param  Builder<JobTitle>  $query
     */
    public function scopeOpenForApplications(Builder $query): void
    {
        $query->where('is_active', true)->where('is_published', true)->where('openings', '>', 0);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
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
