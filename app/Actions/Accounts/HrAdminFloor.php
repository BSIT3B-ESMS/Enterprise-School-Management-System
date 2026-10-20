<?php

namespace App\Actions\Accounts;

use App\Enums\Role;
use App\Models\User;

/**
 * Keeps enough active HR Admin accounts that the system can never be locked out (config ems.minimum_hr_admins).
 */
class HrAdminFloor
{
    /**
     * Throws when taking the account out of service (or out of the HR Admin role) would leave too few active HR Admins.
     *
     * @throws AccountException
     */
    public function assertCanRemove(User $account): void
    {
        if ($account->role !== Role::HrAdmin || ! $account->is_active) {
            return;
        }

        $minimum = (int) config('ems.minimum_hr_admins');
        $remaining = User::query()->where('role', Role::HrAdmin)->where('is_active', true)->whereKeyNot($account->getKey())->count();

        if ($remaining < $minimum) {
            throw new AccountException("At least {$minimum} active HR Admin accounts must remain. Create or activate another HR Admin first.");
        }
    }
}
