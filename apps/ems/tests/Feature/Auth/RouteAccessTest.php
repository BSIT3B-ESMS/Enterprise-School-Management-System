<?php

use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;

/**
 * Which roles may open each protected page. Everyone else gets 403, and anyone not signed in goes to Login.
 */
dataset('protected pages', [
    'dashboard' => ['dashboard', [], [Role::HrAdmin, Role::HrStaff]],
    'employees' => ['employees.index', [], [Role::HrAdmin, Role::HrStaff]],
    'new employee' => ['employees.create', [], [Role::HrAdmin, Role::HrStaff]],
    'employee profile' => ['employees.show', ['employee' => '20-00001'], [Role::HrAdmin, Role::HrStaff]],
    'colleges' => ['colleges.index', [], [Role::HrAdmin, Role::HrStaff]],
    'offices' => ['offices.index', [], [Role::HrAdmin, Role::HrStaff]],
    'job titles' => ['job-titles.index', [], [Role::HrAdmin, Role::HrStaff]],
    'new job title' => ['job-titles.create', [], [Role::HrAdmin]],
    'pay rates' => ['rates.index', [], [Role::HrAdmin, Role::HrStaff]],
    'recruitment' => ['recruitment.index', [], [Role::HrAdmin, Role::HrStaff]],
    'open positions' => ['job-positions.index', [], [Role::HrAdmin, Role::HrStaff]],
    'calendar' => ['calendar.index', [], [Role::HrAdmin, Role::HrStaff]],
    'hr profile' => ['profile.show', [], [Role::HrAdmin, Role::HrStaff]],
    'hr settings' => ['account.settings', [], [Role::HrAdmin, Role::HrStaff]],
    'employees report' => ['employees.report', [], [Role::HrAdmin]],
    'archived employees' => ['employees.archived', [], [Role::HrAdmin]],
    'edit employee' => ['employees.edit', ['employee' => '20-00001'], [Role::HrAdmin, Role::HrStaff]],
    'recruitment report' => ['recruitment.report', [], [Role::HrAdmin]],
    'audit logs' => ['audit.index', [], [Role::HrAdmin]],
    'audit report' => ['audit.report', [], [Role::HrAdmin]],
    'accounts' => ['accounts.index', [], [Role::HrAdmin]],
    'leave requests' => ['leave.index', [], [Role::HrAdmin, Role::HrStaff]],
    'employee dashboard' => ['employee.dashboard', [], [Role::Employee]],
    'employee leave' => ['employee.leave', [], [Role::Employee]],
    'employee profile page' => ['employee.profile', [], [Role::Employee]],
    'employee files' => ['employee.files', [], [Role::Employee]],
    'employee benefits' => ['employee.benefits', [], [Role::Employee]],
    'employee directory' => ['employee.directory', [], [Role::Employee]],
    'employee calendar' => ['employee.calendar', [], [Role::Employee]],
    'employee agreements' => ['employee.offers', [], [Role::Employee]],
    'my leave' => ['my-leave', [], [Role::HrAdmin, Role::HrStaff]],
    'contracts' => ['contracts.index', [], [Role::HrAdmin]],
    'applicant dashboard' => ['applicant.dashboard', [], [Role::Applicant]],
    'applicant profile' => ['applicant.profile', [], [Role::Applicant]],
    'applicant applications' => ['applicant.applications', [], [Role::Applicant]],
    'applicant calendar' => ['applicant.calendar', [], [Role::Applicant]],
    'applicant positions' => ['applicant.positions', [], [Role::Applicant]],
    'notifications' => ['notifications.index', [], [Role::HrAdmin, Role::HrStaff, Role::Employee, Role::Applicant]],
]);

beforeEach(fn () => Employee::factory()->create(['employee_no' => '20-00001']));

it('sends a visitor who is not signed in to Login from :route', function (string $route, array $parameters) {
    $this->get(route($route, $parameters))->assertRedirect(route('login'));
})->with('protected pages');

it('opens :route only for the permitted roles', function (string $route, array $parameters, array $allowed) {
    foreach (Role::cases() as $role) {
        $user = match ($role) {
            Role::Employee => employeeUser(),
            Role::Applicant => applicantUser(),
            default => User::factory()->state(['role' => $role])->create(),
        };
        $response = $this->actingAs($user)->get(route($route, $parameters));

        in_array($role, $allowed, true) ? $response->assertOk() : $response->assertForbidden();
    }
})->with('protected pages');

it('keeps the Careers website, Login, and the Applicant interface open to everyone', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['careers.index', 'login', 'applicant.register']);

it('shows only the HR Admin the Audit Logs and Accounts links', function () {
    $this->actingAs(hrAdmin())->get(route('dashboard'))
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
