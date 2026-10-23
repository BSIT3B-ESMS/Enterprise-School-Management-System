<?php

it('asks for the name in parts, personal details, email, password, and confirm password on Create Account', function () {
    $this->get(route('applicant.register'))
        ->assertSeeInOrder([
            'login-brand', 'PLP Careers', 'Applicant Portal',
            'name="lastName"', 'name="firstName"', 'name="middleName"',
            'name="birthdate"', 'name="sex"', '<option value="">Select sex</option>', 'Prefer not to say',
            'name="civilStatus"', '<option value="">Select civil status</option>', 'Separated',
            'name="email"', 'name="password"', 'autocomplete="new-password"',
            'name="confirmPassword"',
            'at least 8 characters, with an uppercase letter, a lowercase letter, and a number.',
        ], escape: false)
        ->assertDontSee('data-app-shell', escape: false);
});

it('sends anyone not signed in from the applicant pages to the Applicant Portal', function (string $route) {
    $this->get(route($route))
        ->assertSee('data-app-shell data-applicant-shell data-login-url="'.route('applicant.portal').'"', escape: false);
})->with(['applicant.dashboard', 'applicant.profile', 'applicant.calendar', 'applicant.positions', 'applicant.apply', 'applicant.applications']);

it('ends the applicant session from Log Out in the header and the sidebar', function () {
    $html = $this->get(route('applicant.dashboard'))->getContent();

    expect(substr_count($html, 'href="'.route('applicant.portal').'" data-applicant-logout'))->toBe(2)
        ->and($html)->toContain('Log Out')->not->toContain('Exit to Sign In');
});

it('marks every required registration field with an asterisk and nothing else', function () {
    $html = $this->get(route('applicant.register'))->getContent();

    // Log In (email, password) and Create Account (last name, first name, birthdate, sex, civil status, email, password, confirm).
    expect(substr_count($html, 'form-label is-required'))->toBe(10)
        ->and($html)->toContain('data-required-note')
        ->and($html)->not->toContain('for="applicant-register-middle-name" class="form-label is-required"');
});

it('marks the required job application fields and leaves the optional ones unmarked', function () {
    $html = $this->get(route('applicant.apply'))->getContent();

    expect(substr_count($html, 'form-label is-required'))->toBe(5)
        ->and($html)->toContain('for="apply-phone" class="form-label is-required"', 'for="apply-job-title" class="form-label is-required"')
        ->and($html)->toContain('for="apply-birthdate" class="form-label is-required"', 'for="apply-sex" class="form-label is-required"', 'for="apply-civil-status" class="form-label is-required"')
        ->and($html)->not->toContain('for="apply-address" class="form-label is-required"');
});
