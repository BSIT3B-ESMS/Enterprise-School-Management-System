# Verification record

The application was exercised on PHP 8.4 with MariaDB 11.4.9 and SQLite memory databases. Automated tests cover authentication, role and program isolation, first-login changes, password recovery and single-use reset tokens, private notifications, staff activation and token revocation, regular enrollment, two-stage irregular processing, proposal versions and revisions, academic prerequisites and minimum grades, section/class capacity, scheduling restrictions, source imports, API token abilities and PDF generation.

The concurrent-capacity test runs two independent PHP processes against MariaDB. Both attempt finalization for the same last class seat. The test verifies exactly one finalization, one rejection, one COR snapshot and a student count within capacity. Tests also reject a second student in a full section even if a different subject offering still has an available class seat.

Browser checks used synthetic identities. The regular flow was completed through the browser from Registrar proposal to student acceptance to Registrar finalization and COR display. A second synthetic flow was finalized against the MariaDB application database. Responsive layouts were checked at 390×844, 768×1024, 1366×768 and 1920×1080; the mobile menu and internally scrolling timetable were inspected. The official campus data and large realistic academic datasets have not been supplied, so institutional acceptance and load testing remain separate gates.

Run php scripts/test.php mysql for the database-backed suite and php scripts/test.php for the isolated memory suite. The MariaDB-only race test is deliberately skipped on SQLite. The runner takes credentials from the private local configuration but overrides the database name to plp_automated_testing. Do not grant this development test account access to a production database.

JavaScript syntax is checked with node --check public/js/scheduler.js. Dependency lock validation is performed with composer validate. Composer's advisory check reported no known vulnerabilities at installation time; this is not a guarantee about future advisories.

Runtime limitation resolved: Windows reported Laravel cache paths as unwritable despite permitted writes, and PHPUnit rejected its readable bootstrap. The local helpers build derived provider manifests and load the same PHPUnit environment settings explicitly. Framework vendor files were not patched.

Not verified: real Admission/EMS connectivity, real SMTP delivery, official-grade policy correctness, approved COR visual fidelity, institutional permissions approval, production HTTPS/reverse proxy setup, disaster recovery, and production load. These depend on configuration or data outside the supplied document.
Verified local release result: 54 tests and 229 assertions pass on MariaDB. SQLite reports 54 tests, 223 assertions, with the single MariaDB-only concurrency test skipped. Composer configuration validation and the scheduler JavaScript syntax check pass.
