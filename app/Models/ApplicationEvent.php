<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One line in an application's timeline. Append-only: it is never edited or deleted.
 */
#[Fillable(['application_id', 'type', 'actor_id', 'details'])]
class ApplicationEvent extends Model
{
    public const UPDATED_AT = null;

    /**
     * Plain-language names of the event types.
     *
     * @var array<string, string>
     */
    public const LABELS = [
        'applied' => 'Application received',
        'reviewed' => 'Documents reviewed',
        'demo_scheduled' => 'Demo interview scheduled',
        'demo_rescheduled' => 'Demo interview rescheduled',
        'demo_cancelled' => 'Demo interview cancelled',
        'demo_completed' => 'Demo interview completed',
        'interviewer_changed' => 'Interviewer changed',
        'reschedule_requested' => 'Applicant asked to reschedule',
        'offer_issued' => 'Offer issued',
        'offer_updated' => 'Offer edited by HR',
        'offer_accepted' => 'Offer accepted',
        'offer_declined' => 'Offer declined',
        'hired' => 'Hired',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled by the applicant',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Application events cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Application events cannot be deleted.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    public function label(): string
    {
        return self::LABELS[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
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
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
