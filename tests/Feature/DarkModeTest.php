<?php

use App\Models\JobTitle;

it('renders the :route page in dark mode by default', function (string $route) {
    if ($user = userFor($route)) {
        $this->actingAs($user);
    }

    $this->get(route($route))
        ->assertOk()
        ->assertSee('data-bs-theme="dark"', escape: false);
})->with([
    'login' => ['login'],
    'dashboard' => ['dashboard'],
    'employees' => ['employees.index'],
    'new employee' => ['employees.create'],
    'colleges' => ['colleges.index'],
    'offices' => ['offices.index'],
    'job titles' => ['job-titles.index'],
    'recruitment' => ['recruitment.index'],
    'audit logs' => ['audit.index'],
]);

it('renders the public :route page in light mode by default, with its own theme scope', function (string $route, array $parameters) {
    $position = JobTitle::factory()->create(['is_published' => true, 'openings' => 1]);

    $this->get(route($route, $parameters + ($route === 'careers.show' ? ['position' => $position->id] : [])))
        ->assertOk()
        ->assertSee('data-bs-theme="light" data-theme-scope="public"', escape: false)
        ->assertSee('data-theme-toggle', escape: false);
})->with([
    'careers' => ['careers.index', []],
    'position details' => ['careers.show', []],
]);

it('keeps Login, registration, and the applicant pages in dark mode', function (string $route) {
    if ($user = userFor($route)) {
        $this->actingAs($user);
    }

    $this->get(route($route))
        ->assertOk()
        ->assertSee('data-bs-theme="dark"', escape: false)
        ->assertDontSee('data-theme-scope', escape: false);
})->with(['login', 'applicant.register', 'applicant.dashboard', 'employee.dashboard']);
