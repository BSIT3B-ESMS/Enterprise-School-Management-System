<?php

use App\Models\Employee;

beforeEach(fn () => $this->actingAs(hrHead()));

it('marks the sidebar link of the current HR page', function (string $route, array $parameters, string $linkRoute) {
    $this->get(route($route, $parameters))
        ->assertSee('aria-current="page" href="'.route($linkRoute).'"', escape: false);
})->with([
    'employees list' => ['employees.index', [], 'employees.index'],
    'new employee' => ['employees.create', [], 'employees.index'],
    'archived employees' => ['employees.archived', [], 'employees.index'],
    'employee report' => ['employees.report', [], 'employees.index'],
    'colleges' => ['colleges.index', [], 'colleges.index'],
    'offices' => ['offices.index', [], 'offices.index'],
    'job titles' => ['job-titles.index', [], 'job-titles.index'],
    'new job title' => ['job-titles.create', [], 'job-titles.index'],
    'pay rates' => ['rates.index', [], 'job-titles.index'],
    'accounts' => ['accounts.index', [], 'accounts.index'],
]);

it('keeps the employees link current on a profile', function () {
    $employee = Employee::factory()->create();

    $this->get(route('employees.show', $employee))
        ->assertSee('aria-current="page" href="'.route('employees.index').'"', escape: false);
});

it('links Colleges and Offices from the sidebar in place of Departments', function () {
    $this->get(route('dashboard'))
        ->assertSeeInOrder(['Employees', 'Colleges', 'Offices', 'Job Titles'])
        ->assertDontSee('Departments');
});

it('links the employee profile back to the list with a labelled link', function () {
    $this->get(route('employees.show', Employee::factory()->create()))
        ->assertSee('Back to Employees')
        ->assertSee('href="'.route('employees.index').'"', escape: false);
});
