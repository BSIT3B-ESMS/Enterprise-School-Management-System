<?php

it('asks for name, email, password, and confirm password on Create Account, beside the brand panel', function () {
    $this->get(route('applicant.register'))
        ->assertSeeInOrder([
            'login-brand',
            'name="name"', 'name="email"', 'name="password"', 'autocomplete="new-password"',
            'At least 8 characters, with an uppercase letter, a lowercase letter, and a number.',
            'name="confirmPassword"',
        ], escape: false)
        ->assertDontSee('data-app-shell', escape: false);
});

it('sends anyone not signed in from the applicant pages to the shared Login page', function (string $route) {
    $this->get(route($route))
        ->assertSee('data-app-shell data-applicant-shell data-login-url="'.route('login').'"', escape: false);
})->with(['applicant.dashboard', 'applicant.profile', 'applicant.calendar', 'applicant.positions', 'applicant.apply', 'applicant.applications']);

it('ends the applicant session from Log Out in the header and the sidebar', function () {
    $html = $this->get(route('applicant.dashboard'))->getContent();

    expect(substr_count($html, 'href="'.route('login').'" data-applicant-logout'))->toBe(2)
        ->and($html)->toContain('Log Out')->not->toContain('Exit to Sign In');
});

it('marks every required registration field with an asterisk and nothing else', function () {
    $html = $this->get(route('applicant.register'))->getContent();

    expect(substr_count($html, 'form-label is-required'))->toBe(4)
        ->and($html)->toContain('data-required-note');
});

it('marks the required job application fields and leaves the optional ones unmarked', function () {
    $html = $this->get(route('applicant.apply'))->getContent();

    expect(substr_count($html, 'form-label is-required'))->toBe(7)
        ->and($html)->toContain('for="apply-phone" class="form-label is-required"', 'for="apply-job-title" class="form-label is-required"')
        ->and($html)->toContain('for="apply-emergency-name" class="form-label is-required"', 'for="apply-emergency-number" class="form-label is-required"', 'for="apply-emergency-relationship" class="form-label is-required"')
        ->and($html)->not->toContain('for="apply-address" class="form-label is-required"', 'for="apply-birthdate" class="form-label is-required"');
});
