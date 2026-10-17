<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['application_id', 'status', 'idempotency_key', 'attempts', 'last_error', 'student_number', 'sent_at', 'acknowledged_at'])]
class EnrollmentHandoff extends Model
{
    public const PENDING = 'pending';

    public const SENT = 'sent';

    public const ACKNOWLEDGED = 'acknowledged';

    public const FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'sent_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
