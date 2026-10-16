<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/** Sysadmin only (NFR-01): staff accounts are never created through the API. */
class CreateUser extends Command
{
    protected $signature = 'user:create {email} {name} {role : admission|clinic|sysadmin}';

    protected $description = 'Create a staff or sysadmin account and print a temporary password';

    public function handle(AuditLogger $audit): int
    {
        $role = Role::tryFrom($this->argument('role'));
        if (! $role || $role === Role::Applicant) {
            $this->error('Role must be admission, clinic or sysadmin. Applicants register in the portal.');

            return self::FAILURE;
        }
        if (User::withTrashed()->where('email', $this->argument('email'))->exists()) {
            $this->error('That email already has an account.');

            return self::FAILURE;
        }

        $password = Str::password(16);
        $user = User::create(['name' => $this->argument('name'), 'email' => $this->argument('email'), 'password' => $password]);
        $user->assignRole($role->value);
        $audit->record('account.created', "Created {$role->label()} account for {$user->email}", $user);

        $this->info("Created {$role->label()} account {$user->email}.");
        $this->line("Temporary password: {$password}");

        return self::SUCCESS;
    }
}
