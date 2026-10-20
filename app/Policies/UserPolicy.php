<?php

namespace App\Policies;

use App\Models\User;

/**
 * Account administration belongs to the HR Admin. Nobody uses these powers on their own account, so an HR Admin can never
 * lock themselves (or the last HR Admins) out.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isHrAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isHrAdmin();
    }

    public function resetPassword(User $actor, User $target): bool
    {
        return $actor->isHrAdmin() && $actor->isNot($target);
    }

    public function deactivate(User $actor, User $target): bool
    {
        return $actor->isHrAdmin() && $actor->isNot($target);
    }

    public function reactivate(User $actor, User $target): bool
    {
        return $actor->isHrAdmin();
    }

    public function changeRole(User $actor, User $target): bool
    {
        return $actor->isHrAdmin() && $actor->isNot($target);
    }
}
