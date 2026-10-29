<?php

namespace App\Listeners;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Writes sign-ins, sign-outs, and failed sign-ins to the audit log. A failed attempt keeps only the email that was
 * tried, never the password.
 */
class RecordAuthenticationActivity
{
    public function __construct(private AuditLogger $audit) {}

    public function handleLogin(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        $this->audit->log(AuditAction::Login, $event->user, actor: $event->user);
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->audit->log(AuditAction::Logout, $event->user, actor: $event->user);
        }
    }

    public function handleFailed(Failed $event): void
    {
        $this->audit->log(AuditAction::LoginFailed, null, ['email' => $event->credentials['email'] ?? null]);
    }
}
