<?php

namespace App\Models;

use App\Enums\AuditAction;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * An append-only audit entry. Write entries through App\Services\AuditLogger; they can never be edited or deleted
 * through the model.
 */
#[Fillable(['user_id', 'actor_type', 'action', 'entity_type', 'entity_id', 'changes', 'ip_address'])]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit log entries cannot be deleted.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'changes' => 'array',
        ];
    }

    /**
     * One plain sentence about the entry, for the dashboard and the Audit Logs page, with no field values in it.
     */
    public function summary(): string
    {
        $thing = $this->entity_type ? strtolower(trim(preg_replace('/(?<!^)[A-Z]/', ' $0', class_basename($this->entity_type)))) : 'the system';
        $event = $this->changes['event'] ?? null;

        if ($event) {
            return ucfirst(str_replace('_', ' ', $event))." ({$thing})";
        }

        return match ($this->action) {
            AuditAction::Create => "Added $thing",
            AuditAction::Update => "Updated $thing",
            AuditAction::Delete => "Deleted $thing",
            AuditAction::Login => 'Signed in',
            AuditAction::Logout => 'Signed out',
            AuditAction::LoginFailed => 'Failed sign-in',
            AuditAction::Export => "Exported $thing",
            AuditAction::Reveal => "Revealed a government ID of $thing",
        };
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
