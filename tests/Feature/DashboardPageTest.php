<?php

it('renders the dashboard sections in order', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder([
            'Headcount by department',
            'Recruitment pipeline',
            'Hires per year',
            'Recent hires',
            'Recent activity',
        ]);
});

it('renders the mount points that dashboard.js fills with mock data', function () {
    $this->get(route('dashboard'))
        ->assertSee('data-page="dashboard"', escape: false)
        ->assertSeeInOrder([
            'data-dashboard-kpis',
            'data-dashboard-headcounts',
            'data-dashboard-pipeline',
            'data-dashboard-hires-chart',
            'data-dashboard-recent-hires',
            'data-dashboard-activity',
        ], escape: false);
});

it('renders the header mount points that app-shell.js fills with mock data', function () {
    $this->get(route('dashboard'))
        ->assertSee('data-user-name', escape: false)
        ->assertSee('data-notifications', escape: false)
        ->assertSee('data-pending-candidates', escape: false);
});

it('links Add Employee to the new employee page', function () {
    $this->get(route('dashboard'))
        ->assertSee('href="'.route('employees.create').'"', escape: false)
        ->assertDontSee('data-bs-target="#employee-form-modal">', escape: false);
});

it('includes the edit employee modal for records opened from Recent Hires', function () {
    $this->get(route('dashboard'))
        ->assertSee('id="employee-form-modal"', escape: false)
        ->assertSee('Edit Employee');
});

it('renders the employee details and reset demo data modals', function () {
    $this->get(route('dashboard'))
        ->assertSee('id="employee-details-modal"', escape: false)
        ->assertSee('data-bs-target="#reset-demo-modal"', escape: false)
        ->assertSee('id="reset-demo-modal"', escape: false);
});

it('marks the dashboard sidebar link as the current page', function () {
    $this->get(route('dashboard'))
        ->assertSee('aria-current="page"', escape: false)
        ->assertSee('href="'.route('dashboard').'"', escape: false);
});
