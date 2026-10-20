<?php

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    config(['ems.require_mfa' => true]);
});

function userWithTwoFactor(array $state = []): array
{
    $secret = (new Google2FA)->generateSecretKey();
    $user = User::factory()->hrHead()->state([
        'password' => 'correct-horse-battery',
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-one', 'recovery-code-two'])),
        'two_factor_confirmed_at' => now(),
        ...$state,
    ])->create();

    return [$user, $secret];
}

it('sends HR Head and HR Staff without two-factor authentication to set it up', function (Closure $makeUser) {
    $this->actingAs($makeUser())->get(route('dashboard'))->assertRedirect(route('two-factor.setup'));
})->with([
    'hr head' => [fn () => hrHead()],
    'hr staff' => [fn () => hrStaff()],
]);

it('lets an HR user who has confirmed two-factor authentication through', function () {
    [$user] = userWithTwoFactor();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

it('does not force employees to use two-factor authentication', function () {
    $this->actingAs(employeeUser())->get(route('employee.dashboard'))->assertOk();
});

it('keeps the setup page reachable for an HR user who has not set it up yet', function () {
    $this->actingAs(hrHead())->get(route('two-factor.setup'))
        ->assertOk()
        ->assertSee('Set up authenticator app');
});

it('lets an HR user enable and confirm an authenticator app', function () {
    $user = hrHead();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

    $this->post(route('two-factor.enable'));

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();

    $code = (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));
    $this->post(route('two-factor.confirm'), ['code' => $code]);

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();

    $this->get(route('dashboard'))->assertOk();
});

it('refuses to confirm with a wrong code', function () {
    $user = hrHead();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $this->post(route('two-factor.enable'));

    $this->post(route('two-factor.confirm'), ['code' => '000000'])->assertSessionHasErrors();

    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
});

it('does not offer an HR user a way to turn two-factor authentication off', function () {
    [$user] = userWithTwoFactor();

    $this->actingAs($user)->get(route('two-factor.setup'))
        ->assertOk()
        ->assertSee('Recovery codes')
        ->assertDontSee('Turn off two-factor authentication');
});

it('offers an employee a way to turn two-factor authentication off', function () {
    [$user] = userWithTwoFactor(['role' => 'employee']);

    $this->actingAs($user)->get(route('two-factor.setup'))->assertSee('Turn off two-factor authentication');
});

describe('signing in', function () {
    it('asks for the code after the password instead of signing in', function () {
        [$user] = userWithTwoFactor();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'correct-horse-battery'])
            ->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
    });

    it('signs in with a valid code', function () {
        [$user, $secret] = userWithTwoFactor();

        $this->withSession(['login.id' => $user->id, 'login.remember' => false])
            ->post(route('two-factor.login.store'), ['code' => (new Google2FA)->getCurrentOtp($secret)])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    });

    it('refuses a wrong code', function () {
        [$user] = userWithTwoFactor();

        $this->withSession(['login.id' => $user->id, 'login.remember' => false])
            ->post(route('two-factor.login.store'), ['code' => '000000'])
            ->assertSessionHasErrors();

        $this->assertGuest();
    });

    it('signs in with a recovery code, and uses it up', function () {
        [$user] = userWithTwoFactor();

        $this->withSession(['login.id' => $user->id, 'login.remember' => false])
            ->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-one'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        expect($user->fresh()->recoveryCodes())->not->toContain('recovery-code-one')->toContain('recovery-code-two');
    });
});
