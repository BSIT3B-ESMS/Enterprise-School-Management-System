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
