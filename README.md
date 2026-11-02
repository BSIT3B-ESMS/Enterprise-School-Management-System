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

`main` is the only long-lived branch. All work happens on short-lived feature branches.

1. Branch from `main` and name it `<app>/<description>` in lowercase. `<app>` is the team: `ems`, `oas` (Admission), `dms`, `pms`, `es` (Enrollment), or `repo` for root files. Example: `ems/leave-approvals`. CI rejects other names.
2. Keep a PR to one app where possible. A PR that changes two apps needs a review from both teams.
3. Open a pull request to `main`. It merges once:
   - the owning team approves it (see [`.github/CODEOWNERS`](.github/CODEOWNERS)) after the last push,
   - every review conversation is resolved, and
   - the **ci-ok** check passes.
4. PRs are squash-merged, so each PR becomes one commit on `main`, and the branch is deleted afterwards.
5. Nobody pushes directly to `main`.

## CI/CD

All workflows live in [`.github/workflows/`](.github/workflows). GitHub ignores workflow files inside `apps/`.

| Workflow | Runs when | What it does |
|---|---|---|
| `ci.yml` | Every PR and push to `main` | Runs the checks of each app that changed, lints workflow files when they change, checks the branch name, then reports one result as **ci-ok** |
| `ems-ci.yml`, `admission-tests.yml`, `dms-ci.yml`, `es-ci.yml` | Called by `ci.yml` and the deploy workflows | Each app's lint and tests |
| `ems-deploy.yml` | Push to `main` touching `apps/ems/`, or by hand | EMS CI, build, then deploy on the self-hosted Windows runner (`ems-production` environment) |
| `admission-deploy.yml` | By hand only | Admission tests, then deploy to EC2 over SSH (`admission-production` environment) |
| `dms-special-checks.yml` | Weekly, or by hand | Real ClamAV scan test for Document Management |

TAP-PMS has a test suite but no CI yet.

Dependabot opens weekly update PRs for each app's Composer and npm packages (except TAP-PMS) and for the workflow actions.
