<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use Database\Factories\LeaveStatusChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One step in a leave request's history. Append-only: an entry can never be edited or deleted.
 */
#[Fillable(['leave_request_id', 'from_status', 'to_status', 'changed_by', 'note'])]
class LeaveStatusChange extends Model
{
    /** @use HasFactory<LeaveStatusChangeFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Leave history entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Leave history entries cannot be deleted.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => LeaveStatus::class,
            'to_status' => LeaveStatus::class,
        ];
    }

    /**
     * @return BelongsTo<LeaveRequest, $this>
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
