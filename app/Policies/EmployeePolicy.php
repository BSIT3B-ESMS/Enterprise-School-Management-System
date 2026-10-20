<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/**
 * Everyone in HR reads, creates, and edits employee records. Only the HR Head changes an assignment (job title, college,
 * office, salary step), archives or restores a record, or reveals a masked government ID.
 */
class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isHr();
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->isHr();
    }

    public function create(User $user): bool
    {
        return $user->isHr();
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->isHr();
    }

    public function assign(User $user, Employee $employee): bool
    {
        return $user->isHrHead();
    }

    public function archive(User $user, Employee $employee): bool
    {
        return $user->isHrHead();
    }

    public function revealGovernmentId(User $user, Employee $employee): bool
    {
        return $user->isHrHead();
    }
}
