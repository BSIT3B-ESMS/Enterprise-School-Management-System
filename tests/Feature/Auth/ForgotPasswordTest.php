<?php

it('shows the password help page with the PLP HR email', function () {
    $this->get(route('password.help'))
        ->assertOk()
        ->assertSee('Forgot your password?')
        ->assertSee('Email PLP HR')
        ->assertSee(config('ems.hr_email'))
        ->assertSee('mailto:'.config('ems.hr_email'), escape: false);
});

it('links to the password help page from the sign-in form', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('password.help'), escape: false)
        ->assertDontSee('Password reset is not available yet.');
});
