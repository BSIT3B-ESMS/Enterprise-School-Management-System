<?php

use App\Models\Employee;

beforeEach(fn () => $this->actingAs(employeeUser()));

it('serves the employee profile, reached from the account menu rather than the sidebar', function () {
    $this->get(route('employee.profile'))
        ->assertOk()
        ->assertSee('data-page="portal-profile"', escape: false)
        ->assertSee('aria-current="page" href="'.route('employee.profile').'"', escape: false);
});

it('has no Profile item in the employee sidebar', function () {
    $html = $this->get(route('employee.profile'))->getContent();
    $sidebar = substr($html, strpos($html, '<aside class="app-sidebar">'), strpos($html, '</aside>') - strpos($html, '<aside class="app-sidebar">'));

    expect($sidebar)
        ->not->toContain(route('employee.profile'))
        ->not->toContain('Profile')
        ->toContain(route('employee.dashboard'), route('employee.files'), route('employee.benefits'), route('employee.directory'), route('employee.calendar'));
});

it('links Profile from the employee header menu', function () {
    $this->get(route('employee.dashboard'))
        ->assertSeeInOrder(['href="'.route('employee.profile').'"', 'My Profile', 'data-theme-toggle', 'Log Out'], escape: false);
});

it('shows the 201 file sections as tabs with a back link to the dashboard', function () {
    $this->get(route('employee.profile'))
        ->assertSeeInOrder([
            'My Profile', 'Back to Dashboard',
            'data-portal-edit-start>Edit',
            'Personal Information', 'Employment Information', 'Contact Information', 'Government and Tax',
        ], escape: false)
        ->assertSee('href="'.route('employee.dashboard').'"', escape: false);
});

it('shows the personal, employment, contact, and government details', function () {
    $this->get(route('employee.profile'))
        ->assertSeeInOrder([
            'data-portal-field="name"', 'data-portal-field="birthdate"', 'data-portal-field="sex"', 'data-portal-field="civilStatus"',
            'data-portal-field="department"', 'data-portal-field="jobTitle"', 'data-portal-field="hiredOn"', 'data-portal-field="status"',
            'data-portal-field="email"', 'data-portal-field="phone"', 'data-portal-field="address"',
            'data-portal-field="sssNumber"', 'data-portal-field="philhealthNumber"', 'data-portal-field="pagibigNumber"',
            'data-portal-field="tin"', 'data-portal-field="birNumber"',
        ], escape: false);
});

it('lets the employee edit only civil status, phone, and address; the rest is read-only', function () {
    $html = $this->get(route('employee.profile'))->getContent();
    $form = substr($html, strpos($html, 'data-portal-form'));

    expect($form)
        ->toContain('name="civilStatus"', 'name="phone"', 'name="address"')
        ->and($form)->toContain('readonly data-portal-input="name"', 'readonly data-portal-input="birthdate"', 'readonly data-portal-input="sex"', 'readonly data-portal-input="email"')
        ->and($form)->not->toContain('name="name"', 'name="email"', 'name="birthdate"', 'name="sex"', 'name="departmentId"',
            'name="jobTitleId"', 'name="hiredOn"', 'name="salary"', 'name="sssNumber"', 'name="tin"')
        ->and($form)->toContain('data-portal-edit-cancel', 'Save Changes');
});

it('has a Security section with the change password form below the profile', function () {
    $this->get(route('employee.profile'))
        ->assertSeeInOrder([
            'data-portal-form', 'Save Changes',
            'id="security"', 'Security',
            'data-password-form data-password-account="employee"', 'Change Password',
            'for="current-password"', 'Current Password', 'type="password"', 'name="currentPassword"', 'autocomplete="current-password"', 'data-password-toggle',
            'for="new-password"', 'New Password', 'name="newPassword"', 'autocomplete="new-password"', 'data-password-toggle',
            'At least 8 characters, with an uppercase letter, a lowercase letter, and a number.',
            'for="confirm-password"', 'Confirm New Password', 'name="confirmPassword"', 'data-password-toggle',
            'data-password-success', 'Your password has been changed.', 'Update Password',
        ], escape: false);

    expect(substr_count($this->get(route('employee.profile'))->getContent(), 'data-password-toggle>'))->toBe(3);
});

it('links Security from the employee header menu', function () {
    $this->get(route('employee.dashboard'))
        ->assertSee('href="'.route('employee.profile').'#security"', escape: false);
});

it('keeps the HR security section the same, without the employee account marker', function () {
    $this->actingAs(hrHead());
    $this->get(route('profile.show'))
        ->assertSee('id="security"', escape: false)
        ->assertSee('<form novalidate data-password-form>', escape: false)
        ->assertDontSee('data-password-account', escape: false);
});

it('keeps the employee profile free of HR pages and controls', function () {
    $this->get(route('employee.profile'))
        ->assertDontSee('href="'.route('employees.index').'"', escape: false)
        ->assertDontSee('data-employee-delete', escape: false)
        ->assertDontSee('data-profile-edit-start', escape: false);
});

it('leaves the HR employee profile with its edit link and the HR sections', function () {
    $employee = Employee::factory()->create();

    $this->actingAs(hrHead());
    $this->get(route('employees.show', $employee))
        ->assertSee(route('employees.edit', $employee), escape: false)
        ->assertSee('Government IDs')
        ->assertSee('Emergency Contacts');
});
