<?php

it('shows the Login page at the root address', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Welcome Back')
        ->assertSee('data-page="login"', escape: false);
});

it('does not make the dashboard or any other interface the landing page', function () {
    $this->get('/')
        ->assertDontSee('data-page="dashboard"', escape: false)
        ->assertDontSee('data-app-shell', escape: false)
        ->assertDontSee('data-page="applicant-positions"', escape: false);
});

it('still serves the login page at /login, and every interface at its own address', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['login', 'dashboard', 'employee.dashboard', 'applicant.apply', 'applicant.dashboard', 'careers.index']);

it('sends HR and employees back to the root Login page when they log out', function (string $route) {
    $this->get(route($route))->assertSee('href="'.route('home').'"', escape: false);
})->with(['dashboard', 'employee.dashboard']);

it('shows a branded page for addresses that do not exist', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee('href="'.route('home').'"', escape: false)
        ->assertSee('href="'.route('careers.index').'"', escape: false);
});
