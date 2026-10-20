<?php

namespace App\Actions\Accounts;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SessionTerminator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetStaffPassword
{
    public function __construct(private AuditLogger $audit, private SessionTerminator $sessions) {}

    /**
     * Issues a new one-time temporary password, ends the account's sessions, and requires a change at next sign-in.
     * HR verifies the requester's identity before using this; the EMS has no self-service reset.
     *
     * @return string The temporary password, shown once.
     */
    public function handle(User $actor, User $target): string
    {
        return DB::transaction(function () use ($actor, $target): string {
            $temporaryPassword = Str::password(16);

            $target->forceFill([
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'temp_password_expires_at' => now()->addHours((int) config('ems.temporary_password_hours')),
            ])->save();

            $this->sessions->endAll($target);
            $this->audit->log(AuditAction::Update, $target, ['event' => 'password_reset'], $actor);

            return $temporaryPassword;
        });
    }
}
