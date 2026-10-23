<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Seeder;

/**
 * Demo staff accounts, on every server, until the Employment System is
 * connected (staff accounts will come from there; see
 * StaffDirectoryService). Decided by the project owner on 2026-10-24, so
 * the portals can be used before that integration exists. Running it again
 * resets these passwords and re-enables the accounts.
 *
 * Remove them when the Employment System sends real accounts:
 *   php artisan user:active abella_gabrielrey@plpasig.edu.ph off
 *   php artisan user:active ignacio_keane@plpasig.edu.ph off
 */
class DemoStaffAccountsSeeder extends Seeder
{
    public const ACCOUNTS = [
        // email, name, role, password
        ['abella_gabrielrey@plpasig.edu.ph', 'Gabriel Rey Abella', Role::AdmissionHead, 'admin123'],
        ['ignacio_keane@plpasig.edu.ph', 'Keane Ignacio', Role::Clinic, 'clinic123'],
    ];

    public function run(AuditLogger $audit): void
    {
        foreach (self::ACCOUNTS as [$email, $name, $role, $password]) {
            $user = User::withTrashed()->firstOrNew(['email' => $email]);
            $created = ! $user->exists;
            $user->trashed() && $user->restore();
            $user->forceFill([
                'name' => $name,
                'password' => $password,
                'is_active' => true,
                'failed_logins' => 0,
                'locked_until' => null,
                'source' => 'demo',
            ])->save();
            $user->syncRoles([$role->value]);
            $audit->record($created ? 'account.created' : 'account.updated',
                ($created ? 'Created' : 'Reset').' demo '.$role->label()." account {$email} (until the Employment System is connected)", $user, actorName: 'System');
        }
    }
}
