<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\LeaveType;
use Database\Factories\LeavePolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Yearly leave credits for a leave type and employment type. A row with a job title overrides the general row for
 * that title. Credits are HR reference data, not payroll.
 */
#[Fillable(['leave_type', 'employment_type', 'job_title_id', 'days_per_year', 'is_active'])]
class LeavePolicy extends Model
{
    /** @use HasFactory<LeavePolicyFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'leave_type' => LeaveType::class,
            'employment_type' => EmploymentType::class,
            'days_per_year' => 'decimal:1',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The yearly credits an employee has for a leave type: the row for their job title if there is one, otherwise the
     * general row for their employment type. Null when the law does not make the leave available to them (for example
     * maternity leave for a man) or no policy gives them credits (for example part-time staff).
     */
    public static function daysFor(Employee $employee, LeaveType $type): ?float
    {
        if (! $type->isAvailableTo($employee->sex, $employee->marital_status)) {
            return null;
        }

        $policy = static::query()
            ->where('leave_type', $type)
            ->where('employment_type', $employee->employment_type)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('job_title_id')->orWhere('job_title_id', $employee->job_title_id))
            ->orderByRaw('job_title_id is null')
            ->first();

        return $policy ? (float) $policy->days_per_year : null;
    }

    /**
     * @return BelongsTo<JobTitle, $this>
     */
    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }
}
