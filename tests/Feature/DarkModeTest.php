<?php

it('renders the :route page in dark mode by default', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('data-bs-theme="dark"', escape: false);
})->with([
    'login' => ['login'],
    'dashboard' => ['dashboard'],
    'employees' => ['employees.index'],
    'new employee' => ['employees.create'],
    'departments' => ['departments.index'],
    'job titles' => ['job-titles.index'],
    'recruitment' => ['recruitment.index'],
    'audit logs' => ['audit.index'],
]);

it('renders the public :route page in light mode by default, with its own theme scope', function (string $route, array $parameters) {
    $this->get(route($route, $parameters))
        ->assertOk()
        ->assertSee('data-bs-theme="light" data-theme-scope="public"', escape: false)
        ->assertSee('data-theme-toggle', escape: false);
})->with([
    'careers' => ['careers.index', []],
    'position details' => ['careers.show', ['position' => 't5']],
]);

it('keeps Login, registration, and the applicant pages in dark mode', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('data-bs-theme="dark"', escape: false)
        ->assertDontSee('data-theme-scope', escape: false);
})->with(['login', 'applicant.portal', 'applicant.register', 'applicant.apply', 'applicant.dashboard', 'employee.dashboard']);

it('keeps the Terms and Conditions on the system theme, even on the Light Careers pages', function () {
    $this->get(route('careers.index'))
        ->assertSee('data-terms-modal data-theme-follow="system"', escape: false);
});
