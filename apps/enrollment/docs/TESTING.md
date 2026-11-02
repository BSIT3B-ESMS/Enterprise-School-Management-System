# Current verification — October 31, 2026

Run `php scripts/test.php`. The current suite passes **121 tests / 598 assertions**, with no skips in the default run. It creates a unique disposable MariaDB schema and uses actual MariaDB for workflows, API boundaries, independent-process last-seat acceptance and encrypted MySQL dump/restore verification. Two archive-compatibility tests use separately isolated SQLite files; the operational website runs on MariaDB/MySQL.

New HTML parity coverage includes seven-stage pipeline counts and filter links, Academic year and Admission year separation, historical section standing, grade summaries/upcoming labels/prerequisites, complete Student/Professor account fields and privacy, Head queue requested-subject links, forwarded-draft composition retaining reviewed assignments, special-enrollment units, send-to-all filtered sections, section-wide year-end promotion, earlier-term prerequisites and room availability preserving COR snapshots. All preceding enrollment, credit, import, scope, schedule and backup tests remain.

Browser checks used a disposable MariaDB QA schema on a separate localhost server. The pipeline and grade pages rendered correctly, pipeline navigation returned the expected student, and the read-only Student account displayed its Admission, college, program, year, section and enrollment information. Desktop screenshots are stored privately in storage/app/html-parity-pipeline.png and html-parity-grades.png. The viewport override still reported 1280 pixels after requesting 390, so this pass does not claim a newly verified mobile screenshot. QA data is removed after checking.

The operational SQLite source was backed up and 39 application tables were transferred to a fresh MariaDB schema with row comparison and foreign-key checks before switching .env. A MariaDB encrypted backup was created and restored successfully. The existing operational user, password hash and catalog were preserved; no QA student or employee was added to the operational database. Session rows were intentionally recreated, so sign in again after the switch.

The source event-handler inventory and functional destinations are in REFERENCE-INTEGRATION.md. Older verification history follows; its earlier SQLite-only instructions and two-stage acceptance assumptions are superseded.

---
# Current verification — October 31, 2026

Run `php scripts/test.php`. The current SQLite-only suite passes 110 tests / 531 assertions, with no skips. It includes atomic student acceptance/COR creation, independent-process last-seat acceptance, automatic sections, term dates/closure/locking, Professor roster authorization and grades, waiting release/defer, JSON paste and promotion guards. Earlier sections below are historical evidence and contain superseded commands/flows. New desktop browser checks used an isolated QA SQLite database; viewport override was unavailable for mobile verification.

# Verification record

The application was exercised on PHP 8.4 with MariaDB 11.4.9 and SQLite memory databases. Automated tests cover authentication, role and program isolation, first-login changes, password recovery and single-use reset tokens, private notifications, staff activation and token revocation, regular enrollment, two-stage irregular processing, proposal versions and revisions, academic prerequisites and minimum grades, section/class capacity, scheduling restrictions, source imports, API token abilities and PDF generation.

The concurrent-capacity test runs two independent PHP processes against MariaDB. Both attempt finalization for the same last class seat. The test verifies exactly one finalization, one rejection, one COR snapshot and a student count within capacity. Tests also reject a second student in a full section even if a different subject offering still has an available class seat.

Browser checks used synthetic identities. The regular flow was completed through the browser from Registrar proposal to student acceptance to Registrar finalization and COR display. A second synthetic flow was finalized against the MariaDB application database. Responsive layouts were checked at 390×844, 768×1024, 1366×768 and 1920×1080; the mobile menu and internally scrolling timetable were inspected. The official campus data and large realistic academic datasets have not been supplied, so institutional acceptance and load testing remain separate gates.

Run php scripts/test.php mysql for the database-backed suite and php scripts/test.php for the isolated memory suite. The MariaDB-only race test is deliberately skipped on SQLite. The runner takes credentials from the private local configuration but overrides the database name to plp_automated_testing. Do not grant this development test account access to a production database.

JavaScript syntax is checked with node --check public/js/scheduler.js. Dependency lock validation is performed with composer validate. Composer's advisory check reported no known vulnerabilities at installation time; this is not a guarantee about future advisories.

Runtime limitation resolved: Windows reported Laravel cache paths as unwritable despite permitted writes, and PHPUnit rejected its readable bootstrap. The local helpers build derived provider manifests and load the same PHPUnit environment settings explicitly. Framework vendor files were not patched.

Not verified: real Admission/EMS connectivity, real SMTP delivery, official-grade policy correctness, approved COR visual fidelity, institutional permissions approval, production HTTPS/reverse proxy setup, disaster recovery, and production load. These depend on configuration or data outside the supplied document.
Verified Desktop-folder update: 76 tests and 361 assertions pass on MariaDB. SQLite reports 76 tests, 355 assertions, with the single MariaDB-only concurrency test skipped. Composer configuration validation and the scheduler JavaScript syntax check pass.


The Desktop-folder browser check completed the irregular-student request, structured subject evaluation, Department Head proposal, student acceptance, explicit forwarding, Registrar final proposal, second acceptance and finalization. Synthetic evaluation #1 and enrollment #2 remain in the local demo for inspection. Expanded student dashboard and evaluation pages were checked at 390x844, 768x1024, 1366x768 and 1920x1080 with no page-wide horizontal overflow. Modal editing and professor availability display were inspected without browser console errors. New tests cover credit evidence/rejection/duplicate prevention/rollback, returning curriculum assignment preserving COR, stale special acceptance, retention of accepted special classes, API aliases, role boundaries and scoped dashboard details. See REQUIREMENTS-AUDIT.md for the precise remaining limitations.

## Requirements completion verification

On October 31, 2026 the operational settings, professor directory, subject prerequisite editor, scheduler preview, supplied catalog and COR changes were verified against MariaDB. The suite contains 85 tests and includes the two-process last-seat concurrency test. The SQLite suite contains one MariaDB-only skip. New regression coverage checks settings permissions/audit, professor filters, atomic prerequisite edits and cycle rollback, preview without persistence and save-time revalidation, idempotent catalog import and conflicting legacy subjects, year-standing enforcement, COR snapshots/PDF generation and allowlisted table sorting.

A MariaDB encrypted backup was created and successfully restored into the separate plp_backup_verification schema, including schema and foreign-key checks. Application data was not used as a test target. UI checks covered settings, professor filters/details, prerequisite controls, scheduler availability and protected-history preview rejection. Source curriculum counts were checked: BSIT 49 entries / 146 units, BSCS 51 entries / 146 units. No real student or employee records were copied from the supplied Node app.

Live SMTP delivery, Admission/EMS institutional connectivity, public hosting, off-device retention and school acceptance remain unverified without the required external configuration. This is a local verification record, not institutional acceptance.

## SQLite-only architecture update

The user selected SQLite only. The active file is database/enrollment.sqlite; MariaDB is no longer a startup, runtime, test or backup dependency. Existing operational tables were copied from the preserved source and compared row-for-row. WAL, foreign keys, a busy timeout, FULL synchronization and PHP 8.4 BEGIN IMMEDIATE transactions protect writes. The last-seat concurrency test now runs with independent processes against an isolated SQLite file instead of being skipped. Online encrypted backups include committed WAL data and restore to a unique file with integrity, foreign-key and per-table count checks. Earlier MariaDB verification sections are historical records, not the current execution instructions. Run `php scripts/test.php`.
