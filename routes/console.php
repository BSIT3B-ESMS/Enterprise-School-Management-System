<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Transfers dated in the future take effect on their day. The server runs `php artisan schedule:run` every minute.
Schedule::command('transfers:apply')->dailyAt('00:05')->withoutOverlapping();
