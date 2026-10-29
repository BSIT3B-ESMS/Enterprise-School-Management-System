<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An HR calendar entry that is not an interview: a meeting, training, deadline, holiday, or other. An event marked for
 * everyone also shows on the Employee calendar.
 */
#[Fillable(['title', 'type', 'starts_at', 'ends_at', 'venue', 'notes', 'for_everyone', 'status', 'created_by'])]
class CalendarEvent extends Model
{
    /**
     * @var array<string, string>
     */
    public const TYPES = ['meeting' => 'Meeting', 'training' => 'Training', 'deadline' => 'Deadline', 'holiday' => 'Holiday', 'other' => 'Other'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'for_everyone' => 'boolean'];
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
