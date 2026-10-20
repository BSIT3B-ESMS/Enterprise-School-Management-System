<?php

beforeEach(fn () => $this->actingAs(hrHead()));

it('shows the profile menu with the account details, My Profile, Account Settings, Security, and Log Out', function () {
    $this->get(route('dashboard'))
        ->assertSeeInOrder([
            'data-user-initials', 'data-user-name', 'data-user-email', 'data-user-role',
            'href="'.route('profile.show').'"', 'My Profile',
            'Account Settings', 'Security',
            'data-logout>', 'Log Out',
        ], escape: false);
});

it('links Account Settings and Security from the profile menu', function () {
    $this->get(route('dashboard'))
        ->assertSee('href="'.route('account.settings').'"', escape: false)
        ->assertDontSee('disabled>', escape: false)
        ->assertSee('href="'.route('profile.show').'#security"', escape: false);
});

it('renders the change password form with show and hide controls, requirements, and a success message', function () {
    $this->get(route('profile.show'))
        ->assertSeeInOrder([
            'id="security"', 'Security', 'Change Password',
            'for="current-password"', 'Current Password', 'type="password"', 'name="currentPassword"', 'autocomplete="current-password"', 'data-password-toggle',
            'for="new-password"', 'New Password', 'name="newPassword"', 'autocomplete="new-password"', 'data-password-toggle',
            'At least 8 characters, with an uppercase letter, a lowercase letter, and a number.',
            'for="confirm-password"', 'Confirm New Password', 'name="confirmPassword"', 'data-password-toggle',
            'data-password-success', 'Your password has been changed.', 'Update Password',
        ], escape: false)
        ->assertSee('data-password-form', escape: false);

    expect(substr_count($this->get(route('profile.show'))->getContent(), 'data-password-toggle>'))->toBe(3);
});

it('has the profile menu on every HR page and marks My Profile current on its page', function (string $route) {
    $this->get(route($route))->assertSee('href="'.route('profile.show').'"', escape: false);
})->with(['dashboard', 'employees.index', 'recruitment.index', 'audit.index', 'calendar.index']);

it('does not add the profile menu to the login page', function () {
    $this->get(route('login'))
        ->assertDontSee('href="'.route('profile.show').'"', escape: false)
        ->assertDontSee('data-app-shell', escape: false);
});

it('marks My Profile as the current page on the profile page', function () {
    $this->get(route('profile.show'))
        ->assertOk()
        ->assertSee('aria-current="page" href="'.route('profile.show').'"', escape: false);
});

it('renders the profile page full screen with the account details, edit form, and a way back', function () {
    $this->get(route('profile.show'))
        ->assertSee('data-page="hr-profile"', escape: false)
        ->assertSee('data-hr-back-link data-fallback-url="'.route('dashboard').'"', escape: false)
        ->assertSeeInOrder([
            'My Profile', 'Back',
            'data-hr-edit-start>Edit Profile',
            'Personal Information', 'Account Information',
            'data-hr-form',
            'name="name"', 'name="email"', 'readonly',
            'data-hr-edit-cancel>Cancel', 'Save Changes',
        ], escape: false)
        ->assertDontSee('class="modal', escape: false);
});
