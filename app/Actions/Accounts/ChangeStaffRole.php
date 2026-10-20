<?php

namespace App\Actions\Accounts;

use App\Enums\AuditAction;
use App\Enums\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class ChangeStaffRole
{
    public function __construct(private AuditLogger $audit, private HrHeadFloor $floor) {}

    /**
     * Only an HR Head changes roles. Nobody changes their own role, and the last HR Heads cannot be demoted.
     *
     * @throws AccountException
     */
    public function handle(User $actor, User $target, Role $role): void
    {
        if ($actor->is($target)) {
            throw new AccountException('You cannot change your own role.');
        }

        if ($target->role === $role) {
            return;
        }

        $this->floor->assertCanRemove($target);

        DB::transaction(function () use ($actor, $target, $role): void {
            $old = $target->role;

            $target->forceFill(['role' => $role])->save();
            $this->audit->log(AuditAction::Update, $target, ['role' => ['old' => $old->value, 'new' => $role->value]], $actor);
        });
    }
}
