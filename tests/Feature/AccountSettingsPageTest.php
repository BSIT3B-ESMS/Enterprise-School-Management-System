<?php

it('opens Account Settings from the HR profile menu and marks it current on its page', function () {
    $this->get(route('dashboard'))
        ->assertSee('href="'.route('account.settings').'"', escape: false);

    $this->get(route('account.settings'))
        ->assertOk()
        ->assertSee('data-page="account-settings"', escape: false)
        ->assertSee('aria-current="page" href="'.route('account.settings').'"', escape: false);
});

it('has appearance with Light, Dark, and System, and the three notification switches', function () {
    $this->get(route('account.settings'))
        ->assertSeeInOrder([
            'Account Settings',
            'Appearance', 'Theme',
            'id="settings-theme-light"', 'value="light"', 'Light',
            'id="settings-theme-dark"', 'value="dark"', 'Dark',
            'id="settings-theme-system"', 'value="system"', 'System',
            'Notifications',
            'data-settings-notification="leave"', 'Leave requests',
            'data-settings-notification="recruitment"', 'Recruitment updates',
            'data-settings-notification="employees"', 'Employee updates',
        ], escape: false);
});

it('keeps Account Settings separate from My Profile and Security', function () {
    $this->get(route('account.settings'))
        ->assertDontSee('data-password-form', escape: false)
        ->assertDontSee('Change Password', escape: false)
        ->assertDontSee('data-hr-form', escape: false)
        ->assertDontSee('Edit Profile', escape: false);

    $this->get(route('profile.show'))
        ->assertDontSee('data-settings-theme', escape: false)
        ->assertDontSee('data-settings-notification', escape: false);
});

it('adds no display preferences the EMS has no use for', function () {
    $this->get(route('account.settings'))
        ->assertDontSee('Rows per page', escape: false)
        ->assertDontSee('Language', escape: false)
        ->assertDontSee('Date format', escape: false)
        ->assertDontSee('Density', escape: false);
});

it('keeps Account Settings out of the Employee and Applicant interfaces', function () {
    $this->get(route('employee.dashboard'))->assertDontSee(route('account.settings'), escape: false);
    $this->get(route('applicant.dashboard'))->assertDontSee(route('account.settings'), escape: false);
});

it('has the notifications menu an empty message to fall back on', function () {
    $this->get(route('dashboard'))
        ->assertSee('data-notifications', escape: false);
});
