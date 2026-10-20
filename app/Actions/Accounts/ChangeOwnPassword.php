<?php

namespace App\Actions\Accounts;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChangeOwnPassword
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Sets the user's own password (the request has already checked their current one), clears the temporary-password
     * state, and signs out every other session of theirs.
     */
    public function handle(User $user, string $newPassword): void
    {
        DB::transaction(function () use ($user, $newPassword): void {
            $user->forceFill([
                'password' => $newPassword,
                'must_change_password' => false,
                'temp_password_expires_at' => null,
                'password_changed_at' => now(),
            ])->save();

            $this->audit->log(AuditAction::Update, $user, ['event' => 'password_changed'], $user);
        });

        Auth::logoutOtherDevices($newPassword);
    }
}
