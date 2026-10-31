# TAP-PMS original-workflow migration: validation log

**Scope:** Restored key original kiosk and five-tab navigation flows to the previous corrected Laravel 13 + PHP 8.4 + MySQL build.

## Implemented source changes

- Kiosk original HTML layout (clock, TAP branding, RFID buffer, camera, NFC button, verification card) and original CSS copied from v4, not a new kiosk design.
- JavaScript sends each scan and automatic live photo by CSRF-protected POST; the response is displayed **only after** server persistence. No `PMSStore`, localStorage, browser payroll computation or mock employee login.
- `AttendanceService::nextEvent()` resolves next event from server time, assigned effective-dated schedule and existing punches: AM IN/OUT, PM IN/OUT, afternoon-only arrival, invalid windows, a 60-second duplicate guard, and no invented AM work.
- One-time kiosk browser authorization requires real Admin session and MFA. It logs Admin out immediately after activation; kiosk remains authorized only through a revocable server-side session. The token-based alternative is optional.
- Original primary admin navigation: Overview, Workforce & RFID, Approvals, Payroll, Appraisals & Leaves. Original primary employee navigation: My Attendance, Requests, File a Leave, Payslips, My Profile. Server-backed forms, attendance, requests, salary/leave views; extra admin tools remain secondary links.
- Employee account login continues to use official EMS `employee_no`, Admin uses a locally assigned `ADMIN-...` ID. No shared demo password.
- A non-destructive migration tracks accounts suspended specifically due to EMS inactivity and only reactivates those after EMS confirms eligibility.
- Operator-configured 15-minute EMS sync scheduler (opt-in only after actual endpoints confirmed).

## Checks performed here

- All project PHP files: `php -l` syntax validation.
- Local JavaScript: `node --check`.
- ZIP integrity and comparison against corrected baseline.
- Kiosk and EMS-specific Pest regression tests written and reviewed.

## Runtime results and external acceptance

- Composer validation, migrations, application boot and 66 Pest tests / 357 assertions now passed with PHP 8.4 and MySQL 8; see TESTING.md. Windows-specific execution remains a local acceptance step.
- Browser acceptance on Windows, device-specific NFC, phone camera over HTTPS, active user-session/logout flows.
- Live EMS exchange, current HR roster, salary/leave contract, official leave evidence transfer; critical endpoints are not present in the currently visible EMS routes.
- Original dashboard, workforce and payroll UI are **functionally adapted**, not pixel-identical to every v4 screen. Full parity and all original JS behaviors are not verified.
- Real payroll figures/statutory policies must be reconciled with HR; code tests alone cannot sign off business policy.

## Windows verification checklist

1. Back up MySQL + private `storage` and preserve `.env`/`APP_KEY` exactly.
2. Patch the previous integrated project; run `php artisan migrate` and `php artisan optimize:clear`; restart server.
3. Admin `/login` with current ID (e.g. `ADMIN-00001`) and MFA; verify five-tab header.
4. Create or import an official-identifier employee and assign a schedule/card under Workforce.
5. On a separate test date/controlled clock, verify original scan UI and photo uploads; check server-chosen AM IN/OUT, PM IN/OUT, and PM-only, without automatic missing AM entries.
6. File an employee leave request and verify it appears in Admin Approvals. Never mistake pending EMS status for approval.
7. Test the EMS salary-history and approved-leave read-only tables. Obtain endpoint signoff before enabling the live connector.
8. Run Pest **only on a dedicated `tap_pms_testing` database**, not the live database.
9. Reconcile actual payroll with HR sample outputs and test release/withdrawal by employee and cut-off.

This is a source-verified development build, **not** production-complete or live-EMS-certified.
