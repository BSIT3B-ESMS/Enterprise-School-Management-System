<?php

use App\Rules\NotCommonPassword;

it('rejects easy passwords and accepts a hard one', function (string $password, bool $passes) {
    $failed = false;
    (new NotCommonPassword)->validate('password', $password, function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBe(! $passes);
})->with([
    ['Password1', false],
    ['P@ssw0rd', false],
    ['Qwerty123', false],
    ['12345678', false],
    ['aaaaaaaa', false],
    ['Plpasig2026', true],
    ['Tr1cky-Horse-Battery', true],
]);
