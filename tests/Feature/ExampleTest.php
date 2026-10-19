<?php

test('the application opens on PLP Careers, with the login page still at /login', function () {
    $this->get('/')->assertRedirect(route('careers.index'));

    $this->get('/login')->assertStatus(200);
});
