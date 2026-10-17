<?php

it('sends the root address to the existing login page', function () {
    $this->get('/')->assertRedirect(route('login'));
    $this->get(route('home'))->assertRedirect(route('login'));
});

it('shows the sign-in form first when the root address is opened', function () {
    $this->followingRedirects()
        ->get('/')
        ->assertOk()
        ->assertSee('Welcome Back')
        ->assertSee('name="email"', escape: false)
        ->assertSee('name="password"', escape: false)
        ->assertSee('data-login-form', escape: false);
});

it('does not make the dashboard or any other interface the landing page', function () {
    $this->followingRedirects()
        ->get('/')
        ->assertDontSee('data-page="dashboard"', escape: false)
        ->assertDontSee('data-app-shell', escape: false)
        ->assertDontSee('data-page="employee-dashboard"', escape: false)
        ->assertDontSee('data-page="applicant-dashboard"', escape: false);
});

it('still serves the login page at /login, and every interface at its own address', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['login', 'dashboard', 'employee.dashboard', 'applicant.apply', 'applicant.dashboard']);

it('has one login page, with the same address as before', function () {
    expect(route('login', absolute: false))->toBe('/login');
    $this->get('/login')->assertOk()->assertSee('Welcome Back');
});
