# Installing this full aligned release

This ZIP is a full source project, not a partial patch. Use the existing `RUN-ON-WINDOWS.md` and README first-run instructions. PHP must be 8.4.x, with PDO MySQL; MySQL must be 8.

For an existing installation:

1. Back up the MySQL database, private `storage` evidence and existing `.env`/APP_KEY.
2. Extract the new ZIP into a separate folder. Copy your existing `.env` and private evidence into it; keep the new empty cache/session directory scaffold.
3. In the new project's terminal run `composer install`, `php artisan config:clear`, `php artisan migrate`, then `php artisan view:clear`.
4. Start MySQL and `php artisan serve`. Sign in using the existing Admin ID / Employee ID and password. No demo credentials are added.
5. Confirm the payroll policy and assigned schedules, then review a trial period before using real payroll.

PMS owns work schedules. EMS supplies employee records, salaries, leave balances and leave decisions. Do not configure an EMS schedule endpoint.

`php artisan pms:api-contract-check` checks configuration only. It does not certify that EMS routes exist or that live payloads match. Follow `docs/EMS-TEAM-REQUIREMENTS.md`, configure the agreed host/token/paths/mappings, and test both running systems together.

No `.env`, credentials, vendor dependencies, database dump, uploaded evidence or synthetic user accounts are bundled. Composer installs the locked dependencies. Tests must use a separate test database, never your real data.
