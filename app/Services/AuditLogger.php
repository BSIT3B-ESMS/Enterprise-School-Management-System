<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * The one place audit entries are written. Call it inside the same database transaction as the change it records, so
 * the change and its log either both happen or neither does.
 */
class AuditLogger
{
    /**
     * Attributes that must never be copied into an audit entry.
     *
     * @var list<string>
     */
    private const NEVER_LOGGED = ['password', 'remember_token', 'government_ids'];

    /**
     * @param  array<string, mixed>  $changes  Usually the result of changesFor(); anything else a caller wants kept.
     */
    public function log(
        AuditAction $action,
        ?Model $entity = null,
        array $changes = [],
        ?User $actor = null,
        string $actorType = 'user',
    ): AuditLog {
        $actor ??= Auth::user();

        return AuditLog::query()->create([
            'user_id' => $actor?->getKey(),
            'actor_type' => $actor ? $actorType : 'system',
            'action' => $action,
            'entity_type' => $entity ? $entity->getMorphClass() : null,
            'entity_id' => $entity?->getKey(),
            'changes' => $changes === [] ? null : $changes,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * The old and new values of a model's unsaved changes, without any attribute that must stay out of the log.
     * Call it before save(), while the changes are still dirty.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function changesFor(Model $model): array
    {
        $changes = [];

        foreach ($model->getDirty() as $attribute => $value) {
            if (in_array($attribute, self::NEVER_LOGGED, true)) {
                continue;
            }

            $changes[$attribute] = ['old' => $model->getOriginal($attribute), 'new' => $value];
        }

        return $changes;
    }
}
