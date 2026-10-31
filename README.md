# PLP Enrollment Management System

Laravel 12 / PHP 8.4+ / MariaDB (MySQL connection). Bootstrap and jQuery assets are served locally. The operational website now uses MariaDB, following the latest user decision.

## Start

Run `powershell -ExecutionPolicy Bypass -File .\start.ps1` or double-click Start Enrollment System.cmd. Open http://127.0.0.1:8000. Startup configures PHP, starts the existing loopback MariaDB installation on port 3307 when applicable, runs migrations and starts Laravel in the background. Existing occupied HTTP ports are left alone.

For a new installation, install Composer dependencies, configure an existing MariaDB/MySQL database and private credentials in .env, then run scripts/setup-local.ps1. No default accounts or campus records are seeded. Students receive accounts through Admission; staff access is institutionally authorized. There is no public registration or first-account setup screen.

## Integrated workflows

Registrar, Student, program-scoped Department Head and assigned-class Professor portals; seven-stage enrollment pipeline; academic-year and admission-cohort directories; automatic primary sections; section-wide proposals and grade-checked promotion; colleges/programs; subject scopes and earlier-term prerequisites; room-by-hour and weekly scheduling; dates and year-level schedule finalization; special evaluation with evidence-based credits; fixed primary assignments and requested additions; direct or Registrar-forwarded proposals; waiting/defer/release; Professor grades and student summaries/upcoming requirements; private notifications; account details; JSON imports; audit, COR printing, PDFs and history.

Student acceptance creates the COR and reserves seats in one transaction. The Accepted pipeline column includes finalized enrollments because there is no second acceptance or pending Registrar finalization step. Year closure preserves schedules and CORs. Demo credentials, browser-localStorage authority and destructive prototype reset controls were excluded as required by the user's earlier instructions.

See [feature-by-feature mapping](docs/REFERENCE-INTEGRATION.md), [user guide](docs/USER_MANUAL.md) and [API contract](docs/API_DOCUMENTATION.md).

## Verify and recover

Run `php scripts/test.php` with the configured PHP runtime. It creates a unique disposable MariaDB test schema and removes it afterward; it never uses the operational database as a test target. Independent processes verify last-seat acceptance under real MariaDB row locks. Archived SQLite backup compatibility has a separately isolated test; SQLite is not used by the operational app.

Run `php artisan enrollment:backup` for a verified encrypted MariaDB dump, and `php artisan enrollment:verify-backup <file>` to restore it into a unique separate schema and validate checksums, table counts and foreign keys. Protect APP_KEY separately. See [local operations and migration](docs/LOCAL-SETUP.md).

The SQLite source and earlier MariaDB databases were preserved during transfer. 39 application tables were compared row-for-row and foreign keys verified before activating the fresh MariaDB target. Existing passwords and the application key were preserved; transient sessions were not copied, so sign in again after the switch.

Actual Admission/EMS network connectivity, SMTP delivery, current approved curricula, real campus records, institutional acceptance and production hosting still require school inputs. The supplied 2022–2023 BSIT/BSCS catalog remains a labeled historical reference.