# Enterprise School Management System

Monorepo for the five PLP school systems. Each app lives in `apps/` with its full git history and still runs on its own.

| App | Folder | Team | Stack | Database |
|---|---|---|---|---|
| Employee Management System | [`apps/ems`](apps/ems) | `@BSIT3B-ESMS/ems` | Laravel 13, PHP 8.3, Vite | MySQL (`ems`) |
| Time, Attendance & Payroll | [`apps/tap-pms`](apps/tap-pms) | `@BSIT3B-ESMS/pms` | Laravel 13, PHP 8.4 | MySQL (`tap_pms`) |
| Online Admission System | [`apps/admission`](apps/admission) | `@BSIT3B-ESMS/oas` | Laravel 13, PHP 8.3 | SQLite / MySQL |
| Document Management | [`apps/document-management`](apps/document-management) | `@BSIT3B-ESMS/dms` | Laravel 12, PHP 8.2, Vite | MySQL (`dms`) |
| Enrollment System | [`apps/enrollment`](apps/enrollment) | `@BSIT3B-ESMS/es` | Laravel 12, PHP 8.4 | MariaDB (`plp_enrollment`) |

The original per-app repositories are archived. All work now happens here.

## Running an app

```bash
cd apps/<app>
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate        # create the database named in .env first (MySQL/MariaDB apps)
npm install && npm run dev # apps with a package.json
php artisan serve --port=<port>
```

Run each app on its own port so they can all run together, for example `8001` EMS, `8002` Admission, `8003` Document Management, `8004` TAP-PMS and `8005` Enrollment.

Check each app's own README for seeders, demo accounts and start scripts.

## Contributing

1. Branch from `main`, prefixed with the app: `ems/leave-approvals`, `admission/fix-upload`, `dms/...`, `pms/...`, `es/...`. Use `repo/...` for root changes.
2. Keep a PR to one app where possible. A PR that changes two apps needs a review from both teams.
3. Open a pull request to `main`. It merges once:
   - the owning team approves it (see [`.github/CODEOWNERS`](.github/CODEOWNERS)), and
   - the **ci-ok** check passes.
4. Nobody pushes directly to `main`.

## CI/CD

All workflows live in [`.github/workflows/`](.github/workflows). GitHub ignores workflow files inside `apps/`.

| Workflow | Runs when | What it does |
|---|---|---|
| `ci.yml` | Every PR and push to `main` | Runs the checks of each app that changed, then reports one result as **ci-ok** |
| `ems-ci.yml`, `admission-tests.yml`, `dms-ci.yml` | Called by `ci.yml` and the deploy workflows | Each app's lint and tests |
| `ems-deploy.yml` | Push to `main` touching `apps/ems/`, or by hand | EMS CI, build, then deploy on the self-hosted Windows runner (`ems-production` environment) |
| `admission-deploy.yml` | By hand only | Admission tests, then deploy to EC2 over SSH (`admission-production` environment) |
| `dms-special-checks.yml` | Weekly, or by hand | Real ClamAV scan test for Document Management |

TAP-PMS and Enrollment have test suites but no CI yet.

## CI for your app

Apps are moving to one shared pipeline, [`laravel-ci.yml`](.github/workflows/laravel-ci.yml). Maintainers own how CI runs, and each team owns what it runs.

**The pipeline sets up** PHP, the Composer cache, an optional MySQL or MariaDB test database, `npm ci` (if the app needs Node) and `.env` with an app key. It always runs `composer validate` and `composer install`.

**Your team provides** a `ci` script in `apps/<app>/composer.json` that runs your app's checks and exits non-zero on failure:

```json
"scripts": {
    "ci": [
        "vendor/bin/pint --test",
        "@php artisan test --compact"
    ]
}
```

- Run `composer ci` locally before you push. It runs exactly what CI runs.
- Changing the `ci` script only needs your own team's review. Changes to `.github/` need `maintainers`.
- An app that is switched to the shared pipeline without a `ci` script fails with an error pointing here.
- Tell maintainers which PHP version, database (`mysql`, `mariadb` or `none`) and Node setup your app needs. They set those in `ci.yml`.

this is a test by niccolo yumul