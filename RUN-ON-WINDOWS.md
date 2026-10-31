# Run TAP-PMS locally on Windows (existing installation)

You already installed PHP 8.4.27 and Composer 2.11.1 and created a `tap_pms` MySQL database in your earlier v6 setup. **Do not reinstall everything or overwrite the existing database.**

1. Stop the old Laravel server with `Ctrl+C` inside its terminal, if still running.
2. Back up the old TAP-PMS folder, **especially its `.env`, `storage/` private files**, and make a MySQL export of `tap_pms` using phpMyAdmin. Keep the backup secure.
3. Extract this ZIP into a **new, separate** folder, not over the old project. Find `tap-pms-laravel/artisan` and open that folder in VS Code.
4. Copy the old installation's `.env` into this new project's root (because it contains your working APP_KEY and MySQL settings). Do **not** run `php artisan key:generate` again for this existing database.
5. In VS Code, choose **Terminal > New Terminal**. In PowerShell, set the temporary PHP 8.4/Composer PATH:

   ```powershell
   $env:Path = "$env:USERPROFILE\Downloads\php84;$env:LOCALAPPDATA\ComposerSetup\bin;$env:Path"
   php -v
   composer -V
   ```

6. Confirm XAMPP MySQL is running and the `.env` DB credentials are correct. In the new project directory, run:

   ```powershell
   composer install
   php artisan config:clear
   php artisan migrate
   php artisan serve
   ```

7. Open `http://127.0.0.1:8000/login` in the browser. For a **new empty database**, open `/setup` first and create your first admin account. If you kept the old database, log in with the existing admin credentials and authenticator.
8. Open **Settings & Integration**. If EMS is not online, import an official EMS employee CSV for local tests or configure the real EMS endpoints. **No fake employee records appear automatically.**
9. Confirm each employee’s contribution coverage in Workforce and the official deduction mode in Settings. After schedules and attendance are available, compute a trial cut-off, reconcile the itemized figures, then release payslips. See UPGRADE-FROM-V7.md and docs/STATUTORY-RULES.md.

## Developer verification (separate test database)

Create a disposable MySQL test database (not `tap_pms`). Copy `.env.testing.example` to `.env.testing` and configure it, then run `php artisan test`. These tests use `RefreshDatabase` and can destroy records in their selected database.

## API verification

The documented app-to-app endpoints are under `/api/v1`. Issue a permission-scoped app token privately with `php artisan pms:token <caller> --permission=payroll-periods:read` and transmit it only as `Authorization: Bearer ...`; never use this token in browser-side JavaScript.

## If there are errors

- **`php is not recognized` / Composer cannot find PHP:** reopen the terminal and run the temporary PATH line in step 5.
- **`could not find driver`:** enable `extension=pdo_mysql` and `extension_dir` in your PHP 8.4 `php.ini`.
- **`SQLSTATE access denied`:** recheck MySQL username/password in `.env` and run `php artisan config:clear`.
- **`APP_KEY` or 419:** use the original `.env`, check file sessions are writable, and do not rotate the key for an existing database.
- **Empty workforce:** the backend no longer displays the v4 fake employees. Import your approved EMS roster or connect EMS before testing payroll.
