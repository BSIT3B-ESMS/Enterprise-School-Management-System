<?php

it('serves the Applicant Portal as one page with PLP Careers branding on the left and Log In on the right by default', function () {
    $this->get(route('applicant.portal', ['position' => 't5']))
        ->assertOk()
        ->assertSee('data-page="applicant-portal" data-initial-form="login"', escape: false)
        ->assertSee('data-careers-url="'.route('careers.index').'"', escape: false)
        ->assertSeeInOrder([
            'login-brand', 'PLP Careers', 'Applicant Portal',
            'data-auth-position', 'data-portal-position-closed',
            'data-login-for="applicant"', 'data-portal-form="login"',
            'Applicant Login', 'name="email"', 'name="password"', 'Log In</button>',
            'New applicant?', 'data-portal-switch="register">Create Account',
            'data-applicant-register-form data-portal-form="register"',
        ], escape: false)
        ->assertDontSee('data-app-shell', escape: false)
        ->assertDontSee('class="public-header"', escape: false);
});

it('keeps the Create Account form hidden until chosen, and never shows both forms', function () {
    $html = $this->get(route('applicant.portal'))->getContent();

    expect($html)->toContain('class="d-grid gap-3 d-none" novalidate data-applicant-register-form')
        ->and($html)->not->toContain('class="d-grid gap-3 d-none" novalidate data-login-form');

    // The Create Account address is the same page, opened on Create Account: Log In starts hidden.
    $register = $this->get(route('applicant.register'))->getContent();

    expect($register)->toContain('data-initial-form="register"')
        ->and($register)->toContain('class="d-grid gap-3 d-none" novalidate data-login-form');
});

it('has Create Account fields in the portal and a Log In link back', function () {
    $this->get(route('applicant.portal'))
        ->assertSeeInOrder([
            'name="lastName"', 'name="firstName"', 'name="middleName"', 'name="birthdate"', 'name="sex"', 'name="civilStatus"',
            'name="email"', 'name="password"', 'name="confirmPassword"', 'Create Account</button>',
            'Already have an account?', 'data-portal-switch="login">Log In',
        ], escape: false);
});

it('has the terms modal on the portal, opened by Create Account', function () {
    $html = $this->get(route('applicant.portal'))->getContent();

    expect($html)->toContain('id="applicant-terms-modal"')
        ->and(substr_count($html, 'data-portal-switch="register"'))->toBe(1);
});

it('keeps the terms with Accept disabled until checked, a Privacy Notice, and Decline', function () {
    $this->get(route('applicant.portal'))
        ->assertSeeInOrder([
            'Privacy Notice',
            'data-terms-agree',
            'data-terms-decline>Decline</button>',
            'disabled data-terms-accept>Accept and Continue</button>',
        ], escape: false);
});

it('shows the position chosen before signing up on the dashboard, with a way to continue the application', function () {
    $this->get(route('applicant.dashboard'))
        ->assertSee('data-apply-url="'.route('applicant.apply').'"', escape: false)
        ->assertSeeInOrder(['data-applicant-pending-position', 'data-applicant-pending-name', 'data-applicant-pending-apply>Continue Application'], escape: false);
});
