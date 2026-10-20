<?php

use App\Enums\Role;
use App\Models\User;

it('gives a new user the employee role and an active account', function () {
    $user = User::factory()->create()->fresh();

    expect($user->role)->toBe(Role::Employee)
        ->and($user->is_active)->toBeTrue()
        ->and($user->isHr())->toBeFalse();
});

it('treats HR Head and HR Staff as HR but not an employee', function (Role $role, bool $isHr) {
    $user = User::factory()->state(['role' => $role])->create();

    expect($user->isHr())->toBe($isHr);
})->with([
    'hr head' => [Role::HrHead, true],
    'hr staff' => [Role::HrStaff, true],
    'employee' => [Role::Employee, false],
]);

it('ignores a role passed in mass assignment', function () {
    $user = User::query()->create([
        'name' => 'Sneaky Person',
        'email' => 'sneaky_person@plpasig.edu.ph',
        'password' => 'a-long-enough-password',
        'role' => Role::HrHead->value,
        'is_active' => false,
    ])->fresh();

    expect($user->role)->toBe(Role::Employee)
        ->and($user->is_active)->toBeTrue();
});
