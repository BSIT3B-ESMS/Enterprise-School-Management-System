<?php

it('serves the public Careers website without the app shell', function () {
    $this->get(route('careers.index'))
        ->assertOk()
        ->assertSee('PLP Careers')
        ->assertSeeInOrder([
            'Careers at PLP',
            'href="#positions-open-title">View Open Positions',
            'href="'.route('applicant.register').'" data-terms-continue>Create Account',
            'data-positions-open>',
            'Benefits of working at PLP', 'data-careers-benefits',
            'How applying works',
            'Contact us',
        ], escape: false)
        ->assertDontSee('data-app-shell', escape: false);
});

it('shows PLP\'s published contact details on the Careers website', function () {
    $this->get(route('careers.index'))
        ->assertSeeInOrder([
            '12-B Alcalde Jose, Pasig, 1600 Metro Manila',
            'href="tel:+63286431014">2-8643-1014',
            'href="mailto:inquiry@plpasig.edu.ph"',
            'href="https://plpasig.edu.ph"',
            'Contact details as published on the official PLP website.',
        ], escape: false);
});

it('serves Create Account with Login here back to the shared Login page and continues to the dashboard', function () {
    $this->get(route('applicant.register', ['position' => 't5']))
        ->assertOk()
        ->assertSee('data-page="applicant-register"', escape: false)
        ->assertSee('data-back-url="'.route('careers.index').'"', escape: false)
        ->assertSee('novalidate data-applicant-register-form', escape: false)
        ->assertSee('data-dashboard-url="'.route('applicant.dashboard').'"', escape: false)
        ->assertSeeInOrder([
            'Create an Applicant Account',
            'data-auth-position',
            'name="name"', 'name="email"', 'name="password"', 'name="confirmPassword"',
            'Create Account</button>',
            'Already have an account?', 'href="'.route('login').'" data-login-here>Login here</a>',
            'href="'.route('careers.index').'">Back to Careers',
        ], escape: false);
});

it('has no separate applicant login page', function () {
    $this->get('/applicant/login')->assertNotFound();
    $this->get('/jobs')->assertNotFound();
});

it('lays the open positions out three to a row on larger screens', function (string $route) {
    $this->get(route($route))
        ->assertSee('row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3" data-positions-open', escape: false);
})->with(['careers.index', 'applicant.positions']);
