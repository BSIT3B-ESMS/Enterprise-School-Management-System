<?php

namespace App\Actions\Accounts;

use App\Enums\AuditAction;
use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class CreateStaffAccount
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Creates the login for an employee: their official email, and their employee number as the first password. The
     * account must change the password and finish the first-login setup (emergency contact, government and tax IDs)
     * before anything else. Returns the account and the first password so HR can pass it on.
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
            $temporaryPassword = $employee->employee_no;

            $user = new User(['name' => $employee->full_name, 'email' => $employee->official_email, 'password' => $temporaryPassword]);
            $user->forceFill([
                'employee_id' => $employee->id,
                'role' => $role,
                'is_active' => true,
                'must_change_password' => true,
                'onboarding_completed_at' => null,
            ])->save();

            $this->audit->log(AuditAction::Create, $user, ['role' => $role->value, 'email' => $user->email], $actor);

            return [$user, $temporaryPassword];
        });
    }
}
