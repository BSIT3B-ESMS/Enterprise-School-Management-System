<?php

it('serves the applicant dashboard with Dashboard current in the applicant sidebar', function () {
    $this->get(route('applicant.dashboard'))
        ->assertOk()
        ->assertSee('data-page="applicant-dashboard"', escape: false)
        ->assertSee('aria-current="page" href="'.route('applicant.dashboard').'"', escape: false);
});

it('has a sidebar with exactly Dashboard, Profile, Calendar, Open Positions, and Applications in order', function () {
    $html = $this->get(route('applicant.dashboard'))->getContent();
    $sidebar = substr($html, strpos($html, '<aside class="app-sidebar">'), strpos($html, '</aside>') - strpos($html, '<aside class="app-sidebar">'));

    $this->get(route('applicant.dashboard'))
        ->assertSeeInOrder([
            '<aside class="app-sidebar">',
            'href="'.route('applicant.dashboard').'"', 'Dashboard',
            'href="'.route('applicant.profile').'"', 'Profile',
            'href="'.route('applicant.calendar').'"', 'Calendar',
            'href="'.route('applicant.positions').'"', 'Open Positions',
            'href="'.route('applicant.applications').'"', 'Applications',
            'Log Out',
        ], escape: false)
        ->assertDontSee('data-coming-soon', escape: false);

    expect(substr_count($sidebar, 'class="nav-link'))->toBe(5);
});

it('keeps My Profile in the account menu as well', function () {
    $html = $this->get(route('applicant.dashboard'))->getContent();
    $header = substr($html, strpos($html, '<header'), strpos($html, '</header>') - strpos($html, '<header'));

    expect($header)->toContain(route('applicant.profile'), 'My Profile');
});

it('renders the welcome, applications, upcoming schedule, and quick link sections in order', function () {
    $this->get(route('applicant.dashboard'))
        ->assertSeeInOrder([
            'data-applicant-greeting',
            'data-applicant-kpis',
            'Your applications', 'data-applicant-application-list',
            'data-applicant-no-applications', 'You have not applied for a position yet.', 'href="'.route('applicant.positions').'"',
            'Upcoming demo interviews and interviews', 'data-applicant-upcoming', 'No demo interview or interview is scheduled yet.',
            'Quick links',
        ], escape: false)
        ->assertSee('data-application-url="'.route('applicant.applications').'"', escape: false);
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
    $this->get(route($route, ['application' => 'c1', 'position' => 't5']))
        ->assertDontSee('href="'.route('employees.index').'"', escape: false)
        ->assertDontSee('href="'.route('recruitment.index').'"', escape: false)
        ->assertDontSee('href="'.route('dashboard').'"', escape: false)
        ->assertDontSee('href="'.route('employee.dashboard').'"', escape: false)
        ->assertDontSee('href="'.route('profile.show').'"', escape: false)
        ->assertDontSee('href="'.route('employee.profile').'"', escape: false)
        ->assertDontSee('data-user-name', escape: false)
        ->assertDontSee('data-portal-', escape: false);
})->with(['applicant.dashboard', 'applicant.profile', 'applicant.applications.show', 'applicant.applications', 'applicant.calendar', 'applicant.positions', 'applicant.positions.show', 'applicant.apply']);

it('keeps the Employee interface free of the applicant dashboard and profile', function () {
    $this->get(route('employee.dashboard'))
        ->assertDontSee(route('applicant.dashboard'), escape: false)
        ->assertDontSee(route('applicant.profile'), escape: false);
});

it('links the application confirmation to the new application and the dashboard', function () {
    $this->get(route('applicant.apply'))
        ->assertSee('data-application-url="'.route('applicant.applications').'"', escape: false)
        ->assertSeeInOrder([
            'data-apply-success',
            'href="'.route('applicant.dashboard').'">Go to Dashboard',
            'data-apply-application-link>View Application',
        ], escape: false);
});

it('lists each application with its status and progress on the dashboard', function () {
    $this->get(route('applicant.dashboard'))
        ->assertSeeInOrder(['Your applications', 'Current status of each application', 'data-applicant-application-list'], escape: false);
});
