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
