<?php

it('serves the open positions page with Open Positions current in the applicant sidebar', function () {
    $this->get(route('applicant.positions'))
        ->assertOk()
        ->assertSee('data-page="applicant-positions" data-positions-context="portal"', escape: false)
        ->assertSee('data-details-url="'.route('applicant.positions').'"', escape: false)
        ->assertSee('aria-current="page" href="'.route('applicant.positions').'"', escape: false);
});

it('lets anyone browse the open positions before creating an account', function () {
    $this->get(route('careers.index'))
        ->assertOk()
        ->assertSee('data-page="applicant-positions" data-positions-context="public"', escape: false)
        ->assertSee('data-details-url="'.route('careers.index').'"', escape: false)
        ->assertSeeInOrder([
            'data-public-signed-out',
            'href="'.route('login').'">Login</a>',
            'href="'.route('applicant.register').'" data-terms-continue>Create Account',
            'data-public-signed-in', 'href="'.route('applicant.dashboard').'">My Dashboard',
        ], escape: false)
        ->assertDontSee('data-app-shell', escape: false)
        ->assertDontSee('href="'.route('recruitment.index').'"', escape: false);
});

it('lists only open positions, with search, employment type and department filters, and empty states', function (string $route) {
    $this->get(route($route))
        ->assertSeeInOrder([
            'Open Positions',
            'id="positions-open-title"', 'data-positions-open-count',
            'data-positions-search', 'data-positions-type', 'data-positions-department',
            'data-positions-open>',
            'data-positions-none', 'No open positions',
            'data-positions-no-match', 'No positions match your search',
        ], escape: false)
        ->assertDontSee('Closed positions', escape: false);
})->with(['careers.index', 'applicant.positions']);

it('shows a position\'s full details with Apply Now, a closed state, and a link to an existing application', function (string $route) {
    $this->get(route($route, 't5'))
        ->assertOk()
        ->assertSee('data-page="applicant-position"', escape: false)
        ->assertSee('data-position-details data-position-id="t5"', escape: false)
        ->assertSeeInOrder([
            'Back to Open Positions',
            'data-position-missing', 'Position not found',
            'data-position-field="name"', 'data-position-field="department"', 'data-position-status',
            'data-position-apply>Apply Now',
            'disabled data-position-closed>Applications closed', 'data-position-view-application>View My Application',
            'data-position-notice',
            'Employment Type', 'data-position-field="employmentType"', 'Location', 'data-position-field="location"',
            'About the Position', 'data-position-field="description"',
            'Responsibilities', 'data-position-list="responsibilities"',
            'Qualifications', 'data-position-list="qualifications"',
            'Requirements', 'data-position-list="requirements"',
            'Benefits', 'data-position-benefits',
        ], escape: false);
})->with(['careers.show', 'applicant.positions.show']);

it('sends Apply to registration (behind the terms) on the Careers website and to the form in the portal', function () {
    $this->get(route('careers.index'))
        ->assertSee('data-register-url="'.route('applicant.register').'"', escape: false)
        ->assertSee('data-apply-url="'.route('applicant.apply').'"', escape: false);

    $this->get(route('careers.show', 't5'))
        ->assertSee('data-register-url="'.route('applicant.register').'"', escape: false)
        ->assertSee('data-careers-url="'.route('careers.index').'"', escape: false)
        ->assertSee('data-apply-url="'.route('applicant.apply').'"', escape: false);

    $this->get(route('applicant.positions.show', 't5'))
        ->assertSee('data-apply-url="'.route('applicant.apply').'"', escape: false)
        ->assertSee('data-application-url="'.route('applicant.applications').'"', escape: false)
        ->assertSee('aria-current="page" href="'.route('applicant.positions').'"', escape: false);

    $this->get(route('applicant.positions'))
        ->assertSee('data-apply-url="'.route('applicant.apply').'"', escape: false);
});

it('shows only the base salary as pay on the details page and keeps HR pages out of the positions pages', function (string $route) {
    $this->get(route($route, ['position' => 't5']))
        ->assertDontSee('name="salary"', escape: false)
        ->assertDontSee('data-employee-', escape: false)
        ->assertDontSee('href="'.route('employees.index').'"', escape: false)
        ->assertDontSee('href="'.route('recruitment.index').'"', escape: false);
})->with(['careers.index', 'careers.show', 'applicant.positions', 'applicant.positions.show']);

it('explains a closed or already applied position on the application form instead of choosing it', function () {
    $this->get(route('applicant.apply'))
        ->assertSee('aria-current="page" href="'.route('applicant.positions').'"', escape: false)
        ->assertSeeInOrder(['data-apply-none', 'No positions to apply for', 'data-apply-position-notice', 'data-apply-form'], escape: false);
});

it('shows the base salary on the position details page', function (string $route) {
    $this->get(route($route, 't5'))
        ->assertSeeInOrder(['Base Salary', 'data-position-field="salary"', 'Employment Type', 'Location'], escape: false);
})->with(['careers.show', 'applicant.positions.show']);

it('labels the public salary as a range on the position details', function (string $route) {
    $this->get(route($route, 't5'))
        ->assertSee('Base Salary Range', escape: false);
})->with(['careers.show', 'applicant.positions.show']);
