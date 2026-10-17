<?php

it('serves the employee dashboard and sends /employee to it', function () {
    $this->get(route('employee.dashboard'))->assertOk();
    $this->get('/employee')->assertRedirect('/employee/dashboard');
});

it('has an employee sidebar with the five items in order and marks the dashboard current', function () {
    $this->get(route('employee.dashboard'))
        ->assertSee('aria-current="page" href="'.route('employee.dashboard').'"', escape: false)
        ->assertSeeInOrder([
            'Dashboard',
            'href="'.route('employee.files').'">', 'My Files',
            'href="'.route('employee.benefits').'">', 'Benefits',
            'href="'.route('employee.directory').'">', 'Directory',
            'href="'.route('employee.calendar').'">', 'Calendar',
            'Logout',
        ], escape: false)
        ->assertDontSee('data-coming-soon', escape: false);
});

it('renders the welcome, summaries, leave, information, quick links, and benefits sections in order', function () {
    $this->get(route('employee.dashboard'))
        ->assertSee('data-page="employee-dashboard"', escape: false)
        ->assertSeeInOrder([
            'data-portal-greeting',
            'data-portal-kpis',
            'Upcoming approved leave', 'data-portal-leave',
            'My information', 'data-portal-field="id"', 'data-portal-field="department"', 'data-portal-field="jobTitle"',
            'data-portal-field="hiredOn"', 'data-portal-field="email"',
            'Quick links', 'My benefits', 'data-portal-benefits',
        ], escape: false);
});

it('offers a quick link to every employee module', function () {
    $this->get(route('employee.dashboard'))
        ->assertSeeInOrder([
            'class="quick-link" href="'.route('employee.profile').'"',
            'class="quick-link" href="'.route('employee.files').'"',
            'class="quick-link" href="'.route('employee.benefits').'"',
            'class="quick-link" href="'.route('employee.directory').'"',
            'class="quick-link" href="'.route('employee.calendar').'"',
        ], escape: false);
});

it('has its own header with the employee details, theme toggle, and log out', function () {
    $this->get(route('employee.dashboard'))
        ->assertSeeInOrder([
            'href="'.route('employee.dashboard').'"',
            'data-theme-toggle',
            'data-portal-initials', 'data-portal-name', 'data-portal-role',
            'data-portal-email', 'Log Out',
        ], escape: false);
});

it('keeps the employee interface free of HR pages and the HR header', function () {
    $this->get(route('employee.dashboard'))
        ->assertDontSee('href="'.route('employees.index').'"', escape: false)
        ->assertDontSee('href="'.route('recruitment.index').'"', escape: false)
        ->assertDontSee('href="'.route('audit.index').'"', escape: false)
        ->assertDontSee('href="'.route('profile.show').'"', escape: false)
        ->assertDontSee('data-user-name', escape: false)
        ->assertDontSee('data-notifications', escape: false)
        ->assertDontSee('data-logout', escape: false);
});

it('keeps the HR interface free of the employee interface', function () {
    $this->get(route('dashboard'))
        ->assertDontSee(route('employee.dashboard'), escape: false)
        ->assertDontSee('data-portal-', escape: false);
});
