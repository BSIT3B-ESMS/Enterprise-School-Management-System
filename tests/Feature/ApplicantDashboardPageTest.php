<?php

it('serves the applicant dashboard with Dashboard current in the applicant sidebar', function () {
    $this->get(route('applicant.dashboard'))
        ->assertOk()
        ->assertSee('data-page="applicant-dashboard"', escape: false)
        ->assertSee('aria-current="page" href="'.route('applicant.dashboard').'"', escape: false);
});

it('has a sidebar with Dashboard, Calendar, Open Positions, and Applications in order', function () {
    $this->get(route('applicant.dashboard'))
        ->assertSeeInOrder([
            'href="'.route('applicant.dashboard').'"', 'Dashboard',
            'href="'.route('applicant.calendar').'"', 'Calendar',
            'href="'.route('applicant.positions').'"', 'Open Positions',
            'href="'.route('applicant.applications').'"', 'Applications',
            'Exit to Sign In',
        ], escape: false)
        ->assertDontSee('data-coming-soon', escape: false);
});

it('keeps Profile out of the sidebar because it is in the account menu', function () {
    $html = $this->get(route('applicant.dashboard'))->getContent();
    $sidebar = substr($html, strpos($html, '<aside class="app-sidebar">'), strpos($html, '</aside>') - strpos($html, '<aside class="app-sidebar">'));
    $header = substr($html, strpos($html, '<header'), strpos($html, '</header>') - strpos($html, '<header'));

    expect($sidebar)->not->toContain(route('applicant.profile'))->not->toContain('Profile')
        ->and($header)->toContain(route('applicant.profile'), 'My Profile');
});

it('renders the welcome, summary, status, schedule, and quick link sections in order', function () {
    $this->get(route('applicant.dashboard'))
        ->assertSeeInOrder([
            'data-applicant-greeting',
            'data-applicant-missing', 'No application found',
            'data-applicant-kpis',
            'Recruitment status', 'data-applicant-status-pill', 'data-applicant-status-message', 'data-applicant-steps',
            'Application summary', 'data-applicant-field="jobTitle"', 'data-applicant-field="department"',
            'data-applicant-field="appliedOn"', 'data-applicant-field="email"',
            'Upcoming screening and interview', 'data-applicant-upcoming', 'No screening or interview is scheduled yet.',
            'Quick links',
        ], escape: false);
});

it('offers a quick link to every applicant module', function () {
    $this->get(route('applicant.dashboard'))
        ->assertSeeInOrder([
            'class="quick-link" href="'.route('applicant.profile').'"',
            'class="quick-link" href="'.route('applicant.calendar').'"',
            'class="quick-link" href="'.route('applicant.positions').'"',
            'class="quick-link" href="'.route('applicant.applications').'"',
        ], escape: false);
});

it('keeps applicant navigation separate from the HR and Employee interfaces', function (string $route) {
    $this->get(route($route))
        ->assertDontSee('href="'.route('employees.index').'"', escape: false)
        ->assertDontSee('href="'.route('recruitment.index').'"', escape: false)
        ->assertDontSee('href="'.route('dashboard').'"', escape: false)
        ->assertDontSee('href="'.route('employee.dashboard').'"', escape: false)
        ->assertDontSee('href="'.route('profile.show').'"', escape: false)
        ->assertDontSee('href="'.route('employee.profile').'"', escape: false)
        ->assertDontSee('data-user-name', escape: false)
        ->assertDontSee('data-portal-', escape: false);
})->with(['applicant.dashboard', 'applicant.profile', 'applicant.application', 'applicant.applications', 'applicant.calendar', 'applicant.positions']);

it('keeps the Employee interface free of the applicant dashboard and profile', function () {
    $this->get(route('employee.dashboard'))
        ->assertDontSee(route('applicant.dashboard'), escape: false)
        ->assertDontSee(route('applicant.profile'), escape: false);
});

it('sends the application confirmation to the applicant dashboard', function () {
    $this->get(route('applicant.apply'))
        ->assertSee('data-portal-url="'.route('applicant.dashboard').'"', escape: false)
        ->assertSee('data-apply-portal-link>Open My Dashboard', escape: false);
});
