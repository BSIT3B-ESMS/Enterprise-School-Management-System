<?php

test('the application opens on the Login page, with PLP Careers at /careers', function () {
    $this->get('/')->assertOk()->assertSee('Welcome Back');

    $this->get('/login')->assertOk();
    $this->get('/careers')->assertOk();
});
