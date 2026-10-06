<?php

it('renders the :key flash message with the :variant alert class', function (string $key, string $variant) {
    $this->withSession([$key => 'Employee record saved.']);

    $this->get(route('home'))
        ->assertSee('Employee record saved.')
        ->assertSee($variant);
})->with([
    'success' => ['success', 'alert-success'],
    'error' => ['error', 'alert-danger'],
    'warning' => ['warning', 'alert-warning'],
    'info' => ['info', 'alert-info'],
]);

it('renders no alert when the session holds no flash message', function () {
    $this->get(route('home'))
        ->assertDontSee('alert-dismissible');
});
