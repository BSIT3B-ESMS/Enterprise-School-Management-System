<?php

use App\Enums\Role;
use App\Models\User;

it('signs out an HR user who was idle more than 15 minutes', function () {
    $user = User::factory()->hrAdmin()->create();

    $this->actingAs($user)
        ->withSession(['last_activity_at' => now()->subMinutes(16)->timestamp])
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('warning');

    $this->assertGuest();
});

it('keeps an HR user signed in at 14 minutes idle', function () {
    $this->actingAs(hrAdmin())
        ->withSession(['last_activity_at' => now()->subMinutes(14)->timestamp])
        ->get(route('dashboard'))
        ->assertOk();
});

it('gives other staff 30 minutes before signing them out', function () {
    $user = employeeUser();

    $this->actingAs($user)
        ->withSession(['last_activity_at' => now()->subMinutes(29)->timestamp])
        ->get(route('employee.dashboard'))
        ->assertOk();

    $this->actingAs($user)
        ->withSession(['last_activity_at' => now()->subMinutes(31)->timestamp])
        ->get(route('employee.dashboard'))
        ->assertRedirect(route('login'));
});

it('signs out an account that was deactivated, on its next request', function () {
    $user = hrAdmin();
    $user->forceFill(['is_active' => false])->save();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    $this->assertGuest();
});

it('signs out a session whose password hash no longer matches, as after a password change elsewhere', function () {
    $this->actingAs(hrAdmin())
        ->withSession(['password_hash_web' => 'a-stale-hash-from-before-the-change'])
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('sends a user with a temporary password to the change-password page from every staff page', function (string $route) {
    $user = User::factory()->state(['role' => Role::HrAdmin, 'must_change_password' => true])->create();

    $this->actingAs($user)->get(route($route))->assertRedirect(route('password.change'));
})->with(['dashboard', 'employees.index', 'accounts.index']);

it('keeps the change-password page itself reachable with a temporary password', function () {
    $user = User::factory()->state(['must_change_password' => true])->create();

    $this->actingAs($user)->get(route('password.change'))
        ->assertOk()
        ->assertSee('Your first password is your employee ID');
});
