<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An employee's leave request. Once it is approved, rejected, or cancelled it never changes again; every change of status
 * is also kept in leave_status_changes.
 */
#[Fillable(['employee_id', 'leave_type', 'start_date', 'end_date', 'days', 'reason', 'status', 'decided_by', 'decided_at', 'decision_note'])]
class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'leave_type' => LeaveType::class,
            'status' => LeaveStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'days' => 'decimal:1',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * How many working days (Monday to Friday) a request has been waiting for a decision. A pending request waiting more
     * than FLAG_AFTER_WORKING_DAYS is highlighted for HR. The Civil Service rules would approve it automatically; that is
     * not built yet.
     */
    public const FLAG_AFTER_WORKING_DAYS = 5;

    public function workingDaysWaiting(): int
    {
        if ($this->status !== LeaveStatus::Pending) {
            return 0;
        }

        return LeaveType::Vacation->daysBetween($this->created_at->copy()->addDay(), now()) ?: 0;
    }

    public function isOverdue(): bool
    {
        return $this->workingDaysWaiting() > self::FLAG_AFTER_WORKING_DAYS;
    }

    /**
     * Approved leave that covers a date (today by default): what makes an employee "On Leave".
     *
     * @param  Builder<LeaveRequest>  $query
     */
    public function scopeCovering(Builder $query, ?string $date = null): void
    {
        $date ??= now()->toDateString();

        $query->where('status', LeaveStatus::Approved)->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date);
    }

    /**
     * Pending and approved leave that overlaps a date range: the days an employee cannot request again.
     *
     * @param  Builder<LeaveRequest>  $query
     */
    public function scopeOverlapping(Builder $query, string $startDate, string $endDate): void
    {
        $query->whereIn('status', [LeaveStatus::Pending, LeaveStatus::Approved])
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @return HasMany<LeaveStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(LeaveStatusChange::class);
    }
}
