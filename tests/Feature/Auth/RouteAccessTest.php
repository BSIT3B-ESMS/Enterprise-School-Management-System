<?php

use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;

/**
 * Which roles may open each protected page. Everyone else gets 403, and anyone not signed in goes to Login.
 */
dataset('protected pages', [
    'dashboard' => ['dashboard', [], [Role::HrHead, Role::HrStaff]],
    'employees' => ['employees.index', [], [Role::HrHead, Role::HrStaff]],
    'new employee' => ['employees.create', [], [Role::HrHead, Role::HrStaff]],
    'employee profile' => ['employees.show', ['employee' => 'EMP-1001'], [Role::HrHead, Role::HrStaff]],
    'colleges' => ['colleges.index', [], [Role::HrHead, Role::HrStaff]],
    'offices' => ['offices.index', [], [Role::HrHead, Role::HrStaff]],
    'job titles' => ['job-titles.index', [], [Role::HrHead, Role::HrStaff]],
    'new job title' => ['job-titles.create', [], [Role::HrHead]],
    'pay rates' => ['rates.index', [], [Role::HrHead, Role::HrStaff]],
    'recruitment' => ['recruitment.index', [], [Role::HrHead, Role::HrStaff]],
    'open positions' => ['job-positions.index', [], [Role::HrHead, Role::HrStaff]],
    'calendar' => ['calendar.index', [], [Role::HrHead, Role::HrStaff]],
    'hr profile' => ['profile.show', [], [Role::HrHead, Role::HrStaff]],
    'hr settings' => ['account.settings', [], [Role::HrHead, Role::HrStaff]],
    'employees report' => ['employees.report', [], [Role::HrHead]],
    'archived employees' => ['employees.archived', [], [Role::HrHead]],
    'edit employee' => ['employees.edit', ['employee' => 'EMP-1001'], [Role::HrHead, Role::HrStaff]],
    'recruitment report' => ['recruitment.report', [], [Role::HrHead]],
    'audit logs' => ['audit.index', [], [Role::HrHead]],
    'audit report' => ['audit.report', [], [Role::HrHead]],
    'accounts' => ['accounts.index', [], [Role::HrHead]],
    'employee dashboard' => ['employee.dashboard', [], [Role::Employee]],
    'employee profile page' => ['employee.profile', [], [Role::Employee]],
    'employee files' => ['employee.files', [], [Role::Employee]],
    'employee benefits' => ['employee.benefits', [], [Role::Employee]],
    'employee directory' => ['employee.directory', [], [Role::Employee]],
    'employee calendar' => ['employee.calendar', [], [Role::Employee]],
]);

beforeEach(fn () => Employee::factory()->create(['employee_no' => 'EMP-1001']));

it('sends a visitor who is not signed in to Login from :route', function (string $route, array $parameters) {
    $this->get(route($route, $parameters))->assertRedirect(route('login'));
})->with('protected pages');

it('opens :route only for the permitted roles', function (string $route, array $parameters, array $allowed) {
    foreach (Role::cases() as $role) {
        $response = $this->actingAs(User::factory()->state(['role' => $role])->create())
            ->get(route($route, $parameters));

        in_array($role, $allowed, true) ? $response->assertOk() : $response->assertForbidden();
    }
})->with('protected pages');

it('keeps the Careers website, Login, and the Applicant interface open to everyone', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['careers.index', 'login', 'applicant.portal', 'applicant.register', 'applicant.dashboard']);

it('shows only the HR Head the Audit Logs and Accounts links', function () {
    $this->actingAs(hrHead())->get(route('dashboard'))
        ->assertSee('href="'.route('audit.index').'"', escape: false)
        ->assertSee('href="'.route('accounts.index').'"', escape: false);

    $this->actingAs(hrStaff())->get(route('dashboard'))
        ->assertDontSee('href="'.route('audit.index').'"', escape: false)
        ->assertDontSee('href="'.route('accounts.index').'"', escape: false);
});

it('hides Generate Report and Archived Employees from HR Staff', function () {
    $this->actingAs(hrStaff())->get(route('employees.index'))
        ->assertOk()
        ->assertDontSee('Generate Report')
        ->assertDontSee('Archived Employees');

    $this->actingAs(hrStaff())->get(route('recruitment.index'))->assertDontSee('Generate Report');
});
