# Validation — aligned PMS release, 30 October 2026

- **66 automated tests passed; 357 assertions.** PHP 8.4.23, Laravel 13.39.0, Pest 4.7.8, MySQL 8.0.46; fresh migrations.
- Covered: administrator setup/MFA; employee ID/password/status/ownership; all server pages; PMS schedule assignment even with EMS enabled; employee-visible assigned hours; salary changes; four-punch and late PM-only attendance; evidence privacy; kiosk authorization and revocation; request approvals; server payroll, per-cut-off release and stale-input protection; PDF/reports; API permission/revocation/idempotency/cursor behavior; EMS balance ownership and stale data; proof forwarding and invalid response rejection.
- The EMS round-trip test files leave through PMS, sends the request using the configured authenticated adapter, receives a simulated EMS approval and balance, computes payroll and releases a payslip while preserving the EMS balance. HTTP responses are controlled test fixtures, not a deployed EMS server.
- **Headless browser acceptance passed:** original five-tab UI and clock login; setup/MFA; payroll policy; assigned schedule; Employee ID account; computation/release; employee PDF download; report control number; leave filing pending EMS; balance display; profile schedule and 390px responsive layout; automatic kiosk presentation. No JavaScript page errors or page-width overflow were detected. Screenshots were inspected privately.
- Original v4 `style.css` and `tap-theme.css` verified byte-identical. Full workflow mapping is in docs/V4-FUNCTION-CHECK.md.
- Composer strict validation, installed platform requirements and JavaScript syntax checks passed.

Not verified: real EMS host/token/endpoints; physical NFC/camera hardware; Windows installation execution; official statutory/payroll-policy approval; production load and recovery. Real integration requires the EMS changes in docs/EMS-TEAM-REQUIREMENTS.md and an end-to-end run with both teams' servers.

Tests rebuild the dedicated `tap_pms_testing` database. Configure .env.testing from .env.testing.example and grant the test user access only to that database. Never run tests against real records.

V8 regression coverage adds official contribution and semi-monthly tax boundaries, explicit pension coverage, zero-versus-blank overrides, loan/tax treatment, frozen releases, atomic bulk-release rollback, missing-punch corrections, future-punch rejection, grace credit, filtered report exports, all-cut-off employee payslips, EMS employment-change history and local MFA recovery. Tests verify the implemented standard rules; they are not a payroll-office certification.
