<?php

it('adds Apply for a Position at the bottom of the login page and keeps the sign-in form', function () {
    $this->get(route('login'))
        ->assertSeeInOrder([
            'data-login-form',
            'name="email"', 'name="password"', 'Remember Me', 'data-forgot-password', 'Sign In</button>',
            'Looking for a job?', 'href="'.route('applicant.apply').'"', 'Apply for a Position',
        ], escape: false);
});

it('opens the application form without an account, with the existing form controls', function () {
    $this->get(route('applicant.apply'))
        ->assertOk()
        ->assertSee('data-page="applicant-apply"', escape: false)
        ->assertSeeInOrder([
            'Apply for a Position', 'Back to Sign In',
            'data-apply-none', 'No open positions',
            'data-apply-form',
            'name="name"', 'name="email"', 'name="phone"',
            'name="jobTitleId"', 'data-apply-positions',
            'Cancel', 'Submit Application',
            'data-apply-success', 'Application submitted', 'data-apply-portal-link',
        ], escape: false)
        ->assertDontSee('type="password"', escape: false)
        ->assertDontSee('data-app-shell', escape: false)
        ->assertDontSee('href="'.route('employees.index').'"', escape: false);
});

it('serves the application details page in the applicant shell with Applications current', function () {
    $this->get(route('applicant.application'))
        ->assertOk()
        ->assertSee('data-page="applicant-application"', escape: false)
        ->assertSee('aria-current="page" href="'.route('applicant.applications').'"', escape: false)
        ->assertSeeInOrder(['Application Details', 'Back to Applications'], escape: false)
        ->assertSee('Exit to Sign In')
        ->assertSeeInOrder(['data-applicant-initials', 'data-applicant-name', 'data-applicant-email', 'Applicant'], escape: false);
});

it('shows the progress, schedule, and details sections and the not found state on the applicant page', function () {
    $this->get(route('applicant.application'))
        ->assertSeeInOrder([
            'data-applicant-missing', 'No application found', 'href="'.route('applicant.apply').'"',
            'data-applicant-status-message',
            'Progress', 'data-applicant-steps',
            'Screening and interview', 'data-applicant-activities', 'Nothing has been scheduled yet.',
            'Your application', 'data-applicant-field="jobTitle"', 'data-applicant-field="email"',
        ], escape: false);
});

it('gives the applicant no way to change the application or reach HR pages', function () {
    $this->get(route('applicant.application'))
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
        ->assertDontSee('href="'.route('applicant.application').'"', escape: false)
        ->assertDontSee('data-applicant-', escape: false);
})->with(['dashboard', 'employees.index', 'recruitment.index', 'employee.dashboard', 'employee.profile']);
