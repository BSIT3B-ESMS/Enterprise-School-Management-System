<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

/**
 * HR (HR Admin and HR Staff) sees every leave request; only an HR Admin decides them, and never their own. An employee sees, files, and
 * cancels only their own.
 */
class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isHr();
    }

    /**
     * Only an HR Admin approves or rejects leave, and never their own (another HR Admin decides that).
     */
    public function decide(User $user, LeaveRequest $request): bool
    {
        return $user->isHrAdmin() && ($user->employee_id === null || $user->employee_id !== $request->employee_id);
    }

    public function cancel(User $user, LeaveRequest $request): bool
    {
        return $user->employee_id !== null && $user->employee_id === $request->employee_id;
    }
}
