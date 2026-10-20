<?php

use App\Enums\AuditAction;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;

function staffUser(array $state = []): User
{
    return User::factory()->state(['password' => 'correct-horse-battery', ...$state])->create();
}

it('signs a staff member in and sends each role to its own home', function (Role $role, string $home) {
    $user = staffUser(['role' => $role]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'correct-horse-battery'])
        ->assertRedirect(route($home));

    $this->assertAuthenticatedAs($user);
})->with([
    'hr admin' => [Role::HrAdmin, 'dashboard'],
    'hr staff' => [Role::HrStaff, 'dashboard'],
    'employee' => [Role::Employee, 'employee.dashboard'],
]);

it('matches the email without regard to letter case', function () {
    $user = staffUser(['email' => 'santos_juan@plpasig.edu.ph']);

    $this->post(route('login.store'), ['email' => 'Santos_Juan@PLPasig.edu.ph', 'password' => 'correct-horse-battery']);

    $this->assertAuthenticatedAs($user);
});

it('records the sign-in in the audit log and on the account', function () {
    $user = staffUser();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'correct-horse-battery']);

    $entry = AuditLog::query()->where('action', AuditAction::Login)->sole();

    expect($entry->user_id)->toBe($user->id)
        ->and($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects a wrong password and logs the failed attempt without the password', function () {
    $user = staffUser();

    $this->from(route('login'))
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'not-the-password-123'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $entry = AuditLog::query()->where('action', AuditAction::LoginFailed)->sole();

    $this->assertGuest();
    expect($entry->changes)->toBe(['email' => $user->email])
        ->and(json_encode($entry->changes))->not->toContain('not-the-password-123');
});

it('rejects a deactivated account even with the right password', function () {
    $user = staffUser(['is_active' => false]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'correct-horse-battery'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects a temporary password after it has expired but accepts it before', function () {
    $user = staffUser(['must_change_password' => true, 'temp_password_expires_at' => now()->addHour()]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'correct-horse-battery']);
    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'));
    $user->forceFill(['temp_password_expires_at' => now()->subMinute()])->save();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'correct-horse-battery'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('blocks further attempts after five failures, even with the right password', function () {
    $user = staffUser();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password-'.$attempt]);
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'correct-horse-battery'])
        ->assertTooManyRequests();

    $this->assertGuest();
});

it('signs out through a POST to logout and records it', function () {
    $user = staffUser();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect(AuditLog::query()->where('action', AuditAction::Logout)->where('user_id', $user->id)->exists())->toBeTrue();
});

it('sends a signed-in person away from the login page to their home', function () {
    $this->actingAs(employeeUser())->get(route('login'))->assertRedirect(route('employee.dashboard'));
});

it('shows a sign-in error on the login page', function () {
    $user = staffUser();

    $this->from(route('login'))->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password-123']);

    $this->followingRedirects()->get(route('login'))->assertSee('These credentials do not match our records.');
});
