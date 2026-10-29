<?php

beforeEach(fn () => $this->actingAs(hrAdmin()));

it('pins the account menu below its button in every interface header', function (string $route) {
    if ($user = userFor($route)) {
        $this->actingAs($user);
    }

    $this->get(route($route))
        ->assertSee('class="app-user" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"', escape: false)
        ->assertSee('class="dropdown-menu dropdown-menu-end app-menu app-menu-account"', escape: false);
})->with(['dashboard', 'employee.dashboard', 'applicant.dashboard']);

it('keeps the account menu items, actions, and Log Out exactly where they were', function () {
    $this->get(route('dashboard'))
        ->assertSeeInOrder([
            'app-menu-account',
            'My Profile', 'Account Settings', 'Security',
            'data-theme-toggle', 'Log Out',
        ], escape: false);
});

it('leaves the notifications menu to position itself as before', function () {
    $this->get(route('dashboard'))
        ->assertSee('data-bs-toggle="dropdown" aria-expanded="false"', escape: false)
        ->assertSee('aria-label="Notifications', escape: false)
        ->assertSee('class="dropdown-menu dropdown-menu-end app-menu app-menu-wide"', escape: false);
});
