<?php

namespace App\Actions\Accounts;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class ReactivateStaffAccount
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(User $actor, User $target): void
    {
        DB::transaction(function () use ($actor, $target): void {
            $target->forceFill(['is_active' => true])->save();
            $this->audit->log(AuditAction::Update, $target, ['is_active' => ['old' => false, 'new' => true]], $actor);
        });
    }
}
