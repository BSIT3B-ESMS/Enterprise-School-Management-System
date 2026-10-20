<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\College;
use App\Models\CollegeDean;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Support\Collection;

/**
 * Sends in-app notifications. Nothing here emails anyone.
 */
class Notifier
{
    /**
     * Notifies each user once. A null or empty list is fine.
     *
     * @param  User|iterable<User|null>|null  $users
     */
    public function notify(User|iterable|null $users, string $title, string $body, ?string $url = null): void
    {
        $recipients = $users instanceof User ? [$users] : ($users ?? []);

        collect($recipients)->filter()->unique('id')->each(
            fn (User $user) => $user->notify(new InAppNotification($title, $body, $url)),
        );
    }

    /**
     * The accounts of a college's current dean and its College Secretary, so they know of interviews for their college.
     *
     * @return Collection<int, User>
     */
    public function collegeLeaders(?College $college): Collection
    {
        if ($college === null) {
            return collect();
        }

        $deanEmployeeIds = CollegeDean::query()->current()->where('college_id', $college->id)->pluck('employee_id');
        $secretaryEmployeeIds = Employee::query()
            ->where('college_id', $college->id)
            ->whereHas('jobTitle', fn ($titles) => $titles->where('title', 'College Secretary'))
            ->pluck('id');

        return User::query()->where('is_active', true)->whereIn('employee_id', $deanEmployeeIds->merge($secretaryEmployeeIds))->get();
    }

    /**
     * Every active HR Admin.
     *
     * @return Collection<int, User>
     */
    public function hrAdmins(): Collection
    {
        return User::query()->where('role', Role::HrAdmin)->where('is_active', true)->get();
    }

    /**
     * Every active HR Admin and HR Staff.
     *
     * @return Collection<int, User>
     */
    public function hrUsers(): Collection
    {
        return User::query()->whereIn('role', [Role::HrAdmin, Role::HrStaff])->where('is_active', true)->get();
    }
}
