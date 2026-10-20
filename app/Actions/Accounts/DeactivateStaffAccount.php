<?php

namespace App\Actions\Accounts;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SessionTerminator;
use Illuminate\Support\Facades\DB;

class DeactivateStaffAccount
{
    public function __construct(
        private AuditLogger $audit,
        private SessionTerminator $sessions,
        private HrAdminFloor $floor,
    ) {}

    /**
     * Deactivates an account instead of deleting it: login is blocked at once, sessions end, and the history stays.
     *
     * @throws AccountException
     */
    public function handle(User $actor, User $target): void
    {
        if ($actor->is($target)) {
            throw new AccountException('You cannot deactivate your own account.');
        }

        $this->floor->assertCanRemove($target);

        DB::transaction(function () use ($actor, $target): void {
            $target->forceFill(['is_active' => false])->save();
            $this->sessions->endAll($target);
            $this->audit->log(AuditAction::Update, $target, ['is_active' => ['old' => true, 'new' => false]], $actor);
        });
    }
}
