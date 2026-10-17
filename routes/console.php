<?php

use Illuminate\Support\Facades\Schedule;

/*
| Scheduler (architecture: "Queue worker + scheduler"). Run `php artisan
| schedule:work` (or a cron calling schedule:run every minute) next to
| `php artisan queue:work`, which sends the queued emails.
|
| The slot-deadline job goes here once that rule is defined (PROGRESS.md,
| open questions).
*/

Schedule::command('app:backup')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('enrollment:retry')->hourly()->withoutOverlapping(); // no-op while switched off
Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('queue:prune-batches --hours=168')->daily();
