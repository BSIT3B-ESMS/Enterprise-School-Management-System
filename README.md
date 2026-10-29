# PLP Employee Management System (EMS)

A server-rendered Laravel application for the Human Resources office of Pamantasan ng Lungsod ng Pasig (PLP). It covers the employee records, recruitment, leave, and contracts that HR manages, plus self-service portals for employees and applicants and a read-only API for external systems such as Payroll.

## Features

**HR (HR Admin and HR Staff)**
- **Employees**: Faculty and Non-Teaching Staff records with search, filters, profiles, emergency contacts, documents, and masked government IDs. Records are archived, never deleted.
- **Reference data**: colleges, offices, job titles, and effective-dated pay rates (Philippine Salary Grades).
- **Recruitment**: job postings, the application pipeline (Applied → Demo Interview → Offered → Hired), interview scheduling with conflict detection, offers, and hiring. Hiring creates the employee record and their login in one step.
- **Leave**: CSC-based leave credits (earned monthly, prorated for part-time), filing, approval, and rejection with an append-only history.
- **Calendar**: interviews, HR events, and approved leave, with drag-and-drop rescheduling.
- **Contracts**: ending contracts, renewals, and changes of assignment that take effect once the employee signs.
- **In-app notifications** for every role.

**HR Admin only**
- Reports and CSV/XLSX exports (each export is audited), the employee archive, the audit log, account administration, and revealing a government ID (a reason is required and recorded).

**Employee portal** (`/employee`): profile, emergency contacts, files, benefits, directory, calendar, leave, and contract offers.

**Applicant portal** (`/applicant`) and **public Careers site** (`/careers`): browse open positions, register, apply with a resume and portfolio, follow the application, request an interview reschedule, and accept or decline an offer.

**External API** (`/api/v1`): read-only employee and reference data, authenticated with one bearer token per consumer.

## Tech stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.3+ (developed on 8.4), Laravel 13, Laravel Fortify (authentication and 2FA) |
| Database | MySQL |
| Frontend | Blade, Bootstrap 5.3 (Sass), jQuery 4, jQuery UI 1.14 |
| Bundler | Vite 8 with `laravel-vite-plugin` |
| Testing | Pest 5 |
| Formatting | Laravel Pint |

There is no SPA and no Tailwind. All frontend assets are bundled by Vite; do not add CDN `<script>` or `<link>` tags.

## Requirements

- PHP 8.3 or newer with the usual Laravel extensions (`pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, …)
- Composer
- Node.js and npm
- MySQL (one database for the app, one for tests)

## Getting started

```bash
git clone https://github.com/yumulniccolo/EMS.git
cd EMS
cp .env.example .env
```

Create the databases and set the credentials in `.env`:

```sql
CREATE DATABASE ems;
CREATE DATABASE ems_testing;
```

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ems
DB_USERNAME=root
DB_PASSWORD=
```

Then install, migrate, and build:

```bash
composer run setup        # composer install, key:generate, migrate, npm install, npm run build
php artisan db:seed       # synthetic sample data (see below)
composer run dev          # app server, Vite, and the other dev processes
```

The app is served at `http://localhost:8000` and opens on the sign-in page.

### Sample accounts

`php artisan db:seed` loads synthetic data only. The development accounts below are **not created in production** and must never be used there.

| Role | Email | Password |
| --- | --- | --- |
| HR Admin | `yumul_niccolofranco@plpasig.edu.ph` | `password` |
| HR Admin | `bellen_prespurtjames@plpasig.edu.ph` | `password` |
| HR Staff | `manansala_lourdes@plpasig.edu.ph` | their employee number |
| Employee | any seeded employee's official email | their employee number (`YY-00000`) |
| Applicant | e.g. `maricel.soriano@example.test` | `Applicant#2026` |

## Configuration

EMS-specific settings live in [config/ems.php](config/ems.php) and are driven by these environment variables:

| Variable | Default | Purpose |
| --- | --- | --- |
| `EMS_REQUIRE_MFA` | `false` | Require an authenticator app (TOTP) for HR accounts. Must be `true` before production. |
| `EMS_CHECK_BREACHED_PASSWORDS` | `true` | Reject passwords found in known data breaches. |
| `EMS_HR_EMAIL` | `hr@plpasig.edu.ph` | HR contact address shown on the "Forgot password?" page. |

The application timezone is `Asia/Manila`. Mail is sent only to personal email addresses, never to `@plpasig.edu.ph`.

## Scheduled tasks

Future-dated transfers and accepted agreements are applied by scheduled commands (see [routes/console.php](routes/console.php)):

| Command | Runs | Does |
| --- | --- | --- |
| `transfers:apply` | daily at 00:05 | Applies transfers whose effective date has arrived |
| `agreements:apply` | daily at 00:10 | Applies accepted contract offers and changes of assignment on their start date |

The server must run the scheduler every minute, e.g. with cron or Windows Task Scheduler:

```bash
php artisan schedule:run
```

## External API

The read-only API is served at `/api/v1`, returns 50 records per page, and allows 60 requests per minute per consumer. Every call is logged.

| Endpoint | Ability |
| --- | --- |
| `GET /api/v1/employees` | `employees:read` |
| `GET /api/v1/employees/{employeeNo}` | `employees:read` |
| `GET /api/v1/employees/{employeeNo}/status` | `employees:read` |
| `GET /api/v1/colleges` | `reference:read` |
| `GET /api/v1/job-titles` | `reference:read` |
| `GET /api/v1/rate-tiers` | `reference:read` |

Consumers and their tokens are managed from the command line:

```bash
php artisan api:consumer create Payroll --ability=employees:read --ability=reference:read
php artisan api:consumer list
php artisan api:consumer rotate Payroll
php artisan api:consumer revoke Payroll
php artisan api:consumer restore Payroll
```

Send the token as `Authorization: Bearer <token>`.

## Development

| Task | Command |
| --- | --- |
| Run everything for development | `composer run dev` |
| Vite dev server only | `npm run dev` |
| Production asset build | `npm run build` |
| Run the tests | `php artisan test --compact` |
| Run one test file | `php artisan test --compact tests/Feature/HomePageTest.php` |
| Format changed PHP files | `vendor/bin/pint --dirty` |

Tests run against the MySQL database `ems_testing` (see [phpunit.xml](phpunit.xml)).

If a frontend change doesn't show up, or you see "Unable to locate file in Vite manifest", rebuild with `npm run build` or keep `npm run dev` running.

## Project structure

```
app/
├── Actions/        Business operations (Accounts, Employees, Leave, Recruitment)
├── Console/        Artisan commands (api:consumer, transfers:apply, agreements:apply)
├── Enums/          Roles, statuses, leave types, employment types, …
├── Http/           Controllers (web, Applicant, Api\V1), middleware, form requests, API resources
├── Models/         Eloquent models
├── Policies/       Authorization (employees, leave requests, users)
└── Services/       Leave balances, academic calendar, document storage, audit logging, …
database/
├── factories/      Model factories for tests
├── migrations/
└── seeders/        Synthetic sample data
resources/
├── js/             jQuery page scripts
├── scss/           Bootstrap theme and page styles
└── views/          Blade templates per module
routes/
├── web.php         HR, Employee, Applicant, and Careers routes
├── api.php         /api/v1
└── console.php     Scheduled tasks
tests/              Pest feature and unit tests
```

### Notes for contributors and AI agents

- [AGENTS.md](AGENTS.md) is generated by Laravel Boost (`php artisan boost:update`). Don't edit inside its `<laravel-boost-guidelines>` block.
- Durable project guidance lives in [.ai/guidelines/](.ai/guidelines/) (always loaded) and [.ai/rules/](.ai/rules/) (path-scoped; start at [.ai/rules/index.md](.ai/rules/index.md)).
- [.ai/ems-module-guide.md](.ai/ems-module-guide.md) records the domain decisions behind each module.
- Don't change dependencies without approval.
