<?php

it('sends the root address to the login page, not to PLP Careers', function () {
    $this->get('/')->assertRedirect(route('login'));
    $this->get(route('home'))->assertRedirect(route('login'));
});

it('does not make the dashboard or any other interface the landing page', function () {
    $this->followingRedirects()
        ->get('/')
        ->assertSee('Welcome Back')
        ->assertDontSee('data-page="dashboard"', escape: false)
        ->assertDontSee('data-app-shell', escape: false)
        ->assertDontSee('data-page="employee-dashboard"', escape: false)
        ->assertDontSee('data-page="applicant-dashboard"', escape: false);
});

it('still serves the login page at /login, and every interface at its own address', function (string $route) {
    if ($user = userFor($route)) {
        $this->actingAs($user);
    }

    $this->get(route($route))->assertOk();
})->with(['login', 'dashboard', 'employee.dashboard', 'applicant.dashboard', 'careers.index']);

it('has one login page, with the same address as before, linked from PLP Careers', function () {
    expect(route('login', absolute: false))->toBe('/login');
    $this->get('/login')->assertOk()->assertSee('Welcome Back');
    $this->get(route('careers.index'))->assertSee('href="'.route('login').'">Login</a>', escape: false);
});
