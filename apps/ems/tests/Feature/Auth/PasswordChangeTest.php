<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

function userWithTemporaryPassword(): User
{
    return User::factory()->hrAdmin()->state([
        'password' => 'temporary-password-1',
        'must_change_password' => true,
        'temp_password_expires_at' => now()->addHours(10),
    ])->create();
}

function changePassword(User $user, array $input = []): TestResponse
{
    return test()->actingAs($user)->put(route('password.change.update'), [
        'current_password' => 'temporary-password-1',
        'password' => 'My0wnLongPass',
        'password_confirmation' => 'My0wnLongPass',
        ...$input,
    ]);
}

it('offers a show/hide control for each password field', function () {
    $response = $this->actingAs(userWithTemporaryPassword())->get(route('password.change'))->assertOk();
    $html = $response->getContent();

    expect(substr_count($html, 'data-password-toggle>'))->toBe(3)
        ->and($html)->toContain('aria-controls="field-current_password"')
        ->and($html)->toContain('aria-controls="field-password"')
        ->and($html)->toContain('aria-controls="field-password_confirmation"');
});

it('lets a user replace a temporary password with their own', function () {
    $user = userWithTemporaryPassword();

    changePassword($user)->assertRedirect(route('dashboard'));

    $user->refresh();

    expect(Hash::check('My0wnLongPass', $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBeFalse()
        ->and($user->temp_password_expires_at)->toBeNull()
        ->and($user->password_changed_at)->not->toBeNull();
});

it('lets the user into the staff pages once the password is changed', function () {
    $user = userWithTemporaryPassword();
    changePassword($user);

    $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
});

it('audits the change without recording any password', function () {
    $user = userWithTemporaryPassword();
    changePassword($user);

    $entry = AuditLog::query()->where('user_id', $user->id)->latest('id')->first();

    expect($entry->changes)->toBe(['event' => 'password_changed'])
        ->and(json_encode($entry->changes))->not->toContain('My0wnLongPass');
});

it('keeps this session signed in, because it carries the new password hash', function () {
    $user = userWithTemporaryPassword();
    changePassword($user);

    $this->actingAs($user->fresh())
        ->withSession(['password_hash_web' => session('password_hash_web')])
        ->get(route('dashboard'))
        ->assertOk();
});

it('rejects :scenario', function (array $input, string $field) {
    $user = userWithTemporaryPassword();

    changePassword($user, $input)->assertSessionHasErrors($field);

    expect(Hash::check('temporary-password-1', $user->fresh()->password))->toBeTrue();
})->with([
    'a wrong current password' => [['current_password' => 'not-my-password'], 'current_password'],
    'a password shorter than 8 characters' => [['password' => 'Short1a', 'password_confirmation' => 'Short1a'], 'password'],
    'a password with no uppercase letter' => [['password' => 'myownlongpass1', 'password_confirmation' => 'myownlongpass1'], 'password'],
    'a password with no number' => [['password' => 'MyOwnLongPass', 'password_confirmation' => 'MyOwnLongPass'], 'password'],
    'an easy password' => [['password' => 'Password1', 'password_confirmation' => 'Password1'], 'password'],
    'a confirmation that does not match' => [['password_confirmation' => 'something-different'], 'password'],
    'the same password as before' => [['password' => 'temporary-password-1', 'password_confirmation' => 'temporary-password-1'], 'password'],
]);

it('rejects a password found in a known breach when the check is on', function () {
    config(['ems.check_breached_passwords' => true]);
    $hash = strtoupper(sha1('My0wnLongPass'));
    Http::fake(['api.pwnedpasswords.com/range/*' => Http::response(substr($hash, 5).':48210')]);

    changePassword(userWithTemporaryPassword())->assertSessionHasErrors('password');
});

it('accepts a password that is not in any known breach when the check is on', function () {
    config(['ems.check_breached_passwords' => true]);
    Http::fake(['api.pwnedpasswords.com/range/*' => Http::response('0018A45C4D1DEF81644B54AB7F969B88D65:3')]);

    changePassword(userWithTemporaryPassword())->assertSessionHasNoErrors();
});

it('sends a visitor who is not signed in to Login', function () {
    $this->put(route('password.change.update'), [])->assertRedirect(route('login'));
});
