<?php

namespace App\Actions\Accounts;

use App\Enums\AuditAction;
use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateStaffAccount
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Creates the login for an employee, signed in with their official email and a one-time temporary password.
     * Returns the account and the temporary password; it is shown once and never stored in readable form.
     *
     * @return array{0: User, 1: string}
     *
     * @throws AccountException
     */
    public function handle(User $actor, Employee $employee, Role $role): array
    {
        if ($employee->user()->exists() || User::query()->where('email', $employee->official_email)->exists()) {
            throw new AccountException("{$employee->full_name} already has a login account.");
        }

        return DB::transaction(function () use ($actor, $employee, $role): array {
            $temporaryPassword = Str::password(16);

            $user = new User(['name' => $employee->full_name, 'email' => $employee->official_email, 'password' => $temporaryPassword]);
            $user->forceFill([
                'employee_id' => $employee->id,
                'role' => $role,
                'is_active' => true,
                'must_change_password' => true,
                'temp_password_expires_at' => now()->addHours((int) config('ems.temporary_password_hours')),
            ])->save();

            $this->audit->log(AuditAction::Create, $user, ['role' => $role->value, 'email' => $user->email], $actor);

            return [$user, $temporaryPassword];
        });
    }
}
