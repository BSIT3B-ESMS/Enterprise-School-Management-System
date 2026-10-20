<?php

namespace App\Actions\Accounts;

use App\Enums\Role;
use App\Models\User;

/**
 * Keeps enough active HR Head accounts that the system can never be locked out (config ems.minimum_hr_heads).
 */
class HrHeadFloor
{
    /**
     * Throws when taking the account out of service (or out of the HR Head role) would leave too few active HR Heads.
     *
     * @throws AccountException
     */
    public function assertCanRemove(User $account): void
    {
        if ($account->role !== Role::HrHead || ! $account->is_active) {
            return;
        }

        $minimum = (int) config('ems.minimum_hr_heads');
        $remaining = User::query()->where('role', Role::HrHead)->where('is_active', true)->whereKeyNot($account->getKey())->count();

        if ($remaining < $minimum) {
            throw new AccountException("At least {$minimum} active HR Head accounts must remain. Create or activate another HR Head first.");
        }
    }
}
