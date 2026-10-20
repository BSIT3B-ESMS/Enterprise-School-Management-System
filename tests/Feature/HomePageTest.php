<?php

it('sends the root address to PLP Careers, the landing page', function () {
    $this->get('/')->assertRedirect(route('careers.index'));
    $this->get(route('home'))->assertRedirect(route('careers.index'));
});

it('shows PLP Careers first when the root address is opened', function () {
    $this->followingRedirects()
        ->get('/')
        ->assertOk()
        ->assertSee('Build Your Career with PLP')
        ->assertSee('data-page="applicant-positions" data-positions-context="public"', escape: false);
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
    if ($user = userFor($route)) {
        $this->actingAs($user);
    }

    $this->get(route($route))->assertOk();
})->with(['login', 'dashboard', 'employee.dashboard', 'applicant.apply', 'applicant.dashboard', 'careers.index']);

it('has one login page, with the same address as before, linked from PLP Careers', function () {
    expect(route('login', absolute: false))->toBe('/login');
    $this->get('/login')->assertOk()->assertSee('Welcome Back');
    $this->get(route('careers.index'))->assertSee('href="'.route('login').'">Login</a>', escape: false);
});
