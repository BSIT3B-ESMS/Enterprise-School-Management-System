# PLP Enrollment Management System

A working local Laravel 12 / PHP 8.4 / MariaDB enrollment application based on ENROLLMENT-MANAGEMENT-SYSTEM.docx. Bootstrap 5 and jQuery assets are served locally. No frontend build is required.

## Start on this computer

Open PowerShell in this folder:

```powershell
powershell -ExecutionPolicy Bypass -File .\start.ps1
```

Open http://127.0.0.1:8000. The script checks the local database, starts the portable MariaDB server if needed, builds derived provider caches, applies migrations and starts Laravel. It preserves the application key and existing data. Runtime paths in start.ps1 point to the Codex task's work/tools folder on this computer. Use PHP from PATH or update these paths when moving the project.

Demo Registrar: registrar@example.test. Demo password: DemoEnrollment2026!. Other synthetic accounts and complete steps are in docs/USER_MANUAL.md. The demonstration database is labeled in the UI. There are no real student or employee records.

## Included

- Student, Registrar and Department Head portals with server-enforced role, ownership and program boundaries.
- Admission-origin student provisioning; optional one-time email password setup; secure temporary-password change.
- Login throttling, password recovery, session/token revocation and controlled staff account activation.
- Programs, academic years/semesters, curriculum versions, required/elective requirements, subjects and prerequisites.
- Mandatory/alternative prerequisites, optional minimum-grade thresholds, cycle prevention and audited authorized exceptions.
- Academic history, credit records, classification and student-number management.
- Buildings, floors, rooms, specialized/dedicated rooms and recurring room closures.
- EMS professor import, optional teaching authorization synchronization and recorded availability.
- Class offerings, sections, automatic class/section seat counts and full-section recommendations.
- Visual timetable with filters, drag-and-drop creation and changes, keyboard form entry and jQuery AJAX validation.
- Conflict checks for rooms, professors, sections and individual students.
- Regular and two-stage special enrollment proposals, student acceptance, decline/revision remarks and retained history.
- Transactional finalization with capacity checks under MariaDB locks.
- Immutable COR snapshots, HTML printing, server-generated PDF downloads and historical versions.
- Notifications, activity, audit history and filtered CSV enrollment reports.
- Authenticated /api/v1 endpoints and source-scoped Admission/EMS import tokens.
- JSON file exchange using the same validation as the API, duplicate prevention and sanitized import logs.

## Verification

```powershell
php scripts/test.php mysql
php scripts/test.php
node --check public/js/scheduler.js
```

The MySQL/MariaDB suite exclusively uses plp_automated_testing, never the application database. Create that empty test database and grant the local development database user access before running it on another machine. The SQLite suite uses memory and skips the MariaDB-only concurrent-process test. scripts/test.php reads the existing PHPUnit environment settings while avoiding the inaccurate bootstrap-readability check in this Windows execution environment. scripts/warm-cache.php builds derived Laravel provider caches without modifying vendor code.

## Before institutional use

Confirm the actual Admission and EMS contracts, identities and authentication with their teams. The implemented version 1.0 contracts are development contracts from the document, not verified live integrations. Configure real SMTP delivery; the current local environment captures mail to its private development log. Confirm official curriculum, equivalences, grading, academic override authority and COR branding/template.

Set GRADE_ORDER to lower or higher after grading policy is confirmed. Leaving it unconfigured prevents a minimum-grade threshold from being applied with an assumed direction. The two-stage flow is currently applied to all special classifications pending policy confirmation. Pending proposals do not reserve seats; finalization performs the authoritative checks. Section capacity counts distinct finalized students across its offerings, and class capacity is independently enforced.

The official logo, approved COR form, live institutional datasets, live endpoint connection checks, production hosting, backups and user acceptance sign-off still require institutional inputs. No external deployment or GitHub remote was created.

## Source and dependencies

composer install installs locked PHP packages. JavaScript/CSS dependencies are committed under public/vendor. Node is optional for the syntax check. Use .env.example as a placeholder template and configure a private .env. Never commit .env, database contents, mail logs, tokens, runtime tools, or real student exports. Git ignore rules protect the local artifacts.

See docs/DATABASE_ERD.md, docs/API_DOCUMENTATION.md, docs/TESTING.md and docs/USER_MANUAL.md for the schema, interfaces, validation evidence and workflows.