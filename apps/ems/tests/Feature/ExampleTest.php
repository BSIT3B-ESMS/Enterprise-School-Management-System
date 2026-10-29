<?php

test('the application opens on the login page', function () {
    $this->get('/')->assertRedirect(route('login'));

    $this->get('/login')->assertStatus(200);
});
