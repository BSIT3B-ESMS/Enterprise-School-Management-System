<?php

it('serves the public Applicant Portal with Create Account and Login, outside the app shell', function () {
    $this->get(route('applicant.portal', ['position' => 't5']))
        ->assertOk()
        ->assertSee('data-page="applicant-portal"', escape: false)
        ->assertSee('data-careers-url="'.route('careers.index').'"', escape: false)
        ->assertSee('data-register-url="'.route('applicant.register').'"', escape: false)
        ->assertSee('data-login-url="'.route('login').'"', escape: false)
        ->assertSeeInOrder([
            'Applicant Portal',
            'data-portal-position', 'data-portal-position-name', 'data-portal-position-closed',
            'href="'.route('applicant.register').'" data-terms-continue',
            'data-portal-create>Create Account',
            'href="'.route('login').'" data-portal-login>Login',
            'data-portal-back>', 'Back to Open Positions',
        ], escape: false)
        ->assertDontSee('data-app-shell', escape: false);
});

it('has the terms modal on the portal but gives Create Account alone the job of opening it', function () {
    $html = $this->get(route('applicant.portal'))->getContent();

    // The modal is in the page for Create Account to open, and nothing else on the page asks for the terms.
    expect($html)->toContain('id="applicant-terms-modal"')
        ->and(substr_count($html, 'data-portal-create'))->toBe(1)
        ->and($html)->not->toContain('data-terms-continue>Login');
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
