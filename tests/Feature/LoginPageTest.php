<?php

it('renders an empty sign-in form', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Welcome Back')
        ->assertSee('name="email"', escape: false)
        ->assertSee('name="password"', escape: false)
        ->assertDontSee('value="password', escape: false);
});

it('renders the login page without the app navigation', function () {
    $this->get(route('login'))
        ->assertDontSee('id="main-nav"', escape: false);
});
