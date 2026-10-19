<?php

it('keeps one shared Login page, with Login and an Apply for a Job link to the Careers website', function () {
    $this->get(route('login'))
        ->assertSeeInOrder([
            'data-login-form',
            'Welcome Back',
            'name="email"', 'name="password"', 'Remember Me', 'data-forgot-password', 'Login</button>',
            'Looking for a job?',
            'href="'.route('careers.index').'">Apply for a Job</a>',
        ], escape: false)
        ->assertDontSee('Sign In</button>', escape: false)
        ->assertDontSee('data-terms-modal', escape: false)
        ->assertDontSee('href="'.route('applicant.dashboard').'"', escape: false);
});

it('signs applicants in from the shared Login page, with an error area for wrong credentials', function () {
    $this->get(route('login'))
        ->assertSee('data-applicant-dashboard-url="'.route('applicant.dashboard').'"', escape: false)
        ->assertSeeInOrder(['Welcome Back', 'data-login-error', 'name="email"', 'name="password"'], escape: false);
});

it('hides the registration form until the terms are accepted', function () {
    $html = $this->get(route('applicant.register'))->getContent();

    expect($html)->toContain('d-none" novalidate data-applicant-register-form')
        ->and($html)->toContain('id="applicant-terms-modal"');
});

it('asks for the terms and privacy notice before registration', function (string $route) {
    $this->get(route($route, ['position' => 't5']))
        ->assertSeeInOrder([
            'id="applicant-terms-modal"', 'data-bs-backdrop="static"', 'data-terms-modal',
            'Terms and Conditions', 'Privacy Notice',
            'type="checkbox" id="applicant-terms-agree" data-terms-agree',
            'data-terms-decline>Decline</button>',
            'disabled data-terms-accept>Accept and Continue</button>',
            'data-terms-declined-toast',
        ], escape: false);
})->with(['applicant.register', 'careers.index', 'careers.show']);

it('does not claim consent or applications are permanently saved', function (string $route) {
    $this->get(route($route, ['position' => 't5']))
        ->assertSee('kept in this browser only', escape: false)
        ->assertDontSee('permanently', escape: false)
        ->assertDontSee('securely stored', escape: false);
})->with(['applicant.register', 'careers.show']);

it('serves the application details page in the applicant shell with Applications current', function () {
    $this->get(route('applicant.applications.show', 'c1'))
        ->assertOk()
        ->assertSee('data-page="applicant-application" data-applicant-application="c1"', escape: false)
        ->assertSee('aria-current="page" href="'.route('applicant.applications').'"', escape: false)
        ->assertSeeInOrder(['Application Details', 'Back to Applications'], escape: false)
        ->assertSee('data-applicant-logout', escape: false)
        ->assertSeeInOrder(['data-applicant-initials', 'data-applicant-name', 'data-applicant-email', 'Applicant'], escape: false);
});

it('only accepts application and position IDs in the applicant addresses', function (string $uri) {
    $this->get($uri)->assertNotFound();
})->with(['/applicant/applications/EMP-1001', '/applicant/positions/d1', '/jobs/abc']);

it('shows the progress, schedule, position, and submitted details, with a not found state', function () {
    $this->get(route('applicant.applications.show', 'c1'))
        ->assertSeeInOrder([
            'data-applicant-missing', 'No application found', 'not one of yours',
            'data-applicant-status-message',
            'Progress', 'data-applicant-steps',
            'Demo Interview and interview', 'data-applicant-activities', 'Nothing has been scheduled yet.',
            'Position', 'data-applicant-field="employmentType"', 'data-applicant-field="location"',
            'Your application', 'data-applicant-field="email"', 'data-applicant-documents="education"',
        ], escape: false);
});

it('gives the applicant no way to change the application or reach HR pages', function () {
    $this->get(route('applicant.applications.show', 'c1'))
        ->assertDontSee('data-application-', escape: false)
        ->assertDontSee('<form', escape: false)
        ->assertDontSee('href="'.route('employees.index').'"', escape: false)
        ->assertDontSee('href="'.route('recruitment.index').'"', escape: false)
        ->assertDontSee('href="'.route('employee.dashboard').'"', escape: false)
        ->assertDontSee('data-user-name', escape: false)
        ->assertDontSee('data-portal-', escape: false);
});

it('keeps the HR and Employee interfaces free of the applicant interface', function (string $route) {
    $this->get(route($route))
        ->assertDontSee('href="'.route('applicant.apply').'"', escape: false)
        ->assertDontSee('href="'.route('applicant.applications').'"', escape: false)
        ->assertDontSee('data-applicant-', escape: false)
        ->assertDontSee('data-terms-modal', escape: false);
})->with(['dashboard', 'employees.index', 'recruitment.index', 'employee.dashboard', 'employee.profile']);
