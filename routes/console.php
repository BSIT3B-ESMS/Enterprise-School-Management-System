<?php

use Illuminate\Support\Facades\Schedule;

/*
| Scheduler (architecture: "Queue worker + scheduler"). Run `php artisan
| schedule:work` (or a cron calling schedule:run every minute) next to
| `php artisan queue:work`, which sends the queued emails.
|
| Enrollment hand-off and slot-deadline jobs go here once those rules are
| defined (PROGRESS.md, open items).
*/

Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('queue:prune-batches --hours=168')->daily();
