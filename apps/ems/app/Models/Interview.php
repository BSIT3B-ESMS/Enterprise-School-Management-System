<?php

namespace App\Models;

use App\Enums\InterviewMode;
use App\Enums\InterviewStatus;
use Database\Factories\InterviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A demo interview of an application: when, where (a venue or an online link), and who interviews. The applicant can ask to
 * reschedule or, more than 24 hours ahead, cancel the application.
 */
#[Fillable([
    'application_id', 'interviewer_id', 'scheduled_by', 'starts_at', 'ends_at', 'mode', 'venue', 'meeting_link', 'status', 'notes',
    'reschedule_requested_at', 'reschedule_reason', 'reschedule_available_days', 'completed_at', 'cancelled_at', 'cancel_reason',
])]
class Interview extends Model
{
    /** @use HasFactory<InterviewFactory> */
    use HasFactory;

    /**
     * An applicant cannot cancel when the interview is closer than this many hours.
     */
    public const CANCEL_CUTOFF_HOURS = 24;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'mode' => InterviewMode::class,
            'status' => InterviewStatus::class,
            'reschedule_requested_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Interview>  $query
     */
    public function scopeScheduled(Builder $query): void
    {
        $query->where('status', InterviewStatus::Scheduled);
    }

    /**
     * Scheduled interviews that overlap a time span.
     *
     * @param  Builder<Interview>  $query
     */
    public function scopeOverlapping(Builder $query, \DateTimeInterface $start, \DateTimeInterface $end): void
    {
        $query->scheduled()->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }

    /**
     * Whether the applicant can still cancel: not once the interview is within the cutoff, or has passed.
     */
    public function canBeCancelledByApplicant(): bool
    {
        return $this->status === InterviewStatus::Scheduled && $this->starts_at->gt(now()->addHours(self::CANCEL_CUTOFF_HOURS));
    }

    public function isFinished(): bool
    {
        return $this->status !== InterviewStatus::Scheduled;
    }

    /**
     * Where the interview happens, in a few words: the venue or "Online".
     */
    public function placeLabel(): string
    {
        return $this->mode === InterviewMode::Online ? 'Online' : ($this->venue ?: 'Venue to be announced');
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }
}
