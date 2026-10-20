<?php

namespace App\Policies;

use App\Models\User;

/**
 * Account administration belongs to the HR Head. Nobody uses these powers on their own account, so an HR Head can never
 * lock themselves (or the last HR Heads) out.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isHrHead();
    }

    public function create(User $actor): bool
    {
        return $actor->isHrHead();
    }

    public function resetPassword(User $actor, User $target): bool
    {
        return $actor->isHrHead() && $actor->isNot($target);
    }

    public function deactivate(User $actor, User $target): bool
    {
        return $actor->isHrHead() && $actor->isNot($target);
    }

    public function reactivate(User $actor, User $target): bool
    {
        return $actor->isHrHead();
    }

    public function changeRole(User $actor, User $target): bool
    {
        return $actor->isHrHead() && $actor->isNot($target);
    }
}
