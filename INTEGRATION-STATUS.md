# TAP-PMS Integration Status

## What is in this release

**Source:** original TAP UI files from the supplied v4 ZIP; real Laravel 13 backend from the supplied v6 ZIP; `API(1).md` from the system-integration team. This is a combined **server-rendered version**, not the original browser demo and not a claim of production certification.

| Area | Code included | Verification in this build | Remaining external requirement |
|---|---|---|---|
| Visual UI | Original v4 TAP navy/brass, animated login clock, masthead, horizontal ribbon, page bar, styles + responsive server-adapted Blade screens | Original source CSS inspected; changed source checked | Browser side-by-side visual approval on user laptop |
| Authentication | Server sessions, password hashing, CSRF, first-run admin, MFA and recovery codes, employee account access | PHP static lint; inherited v6 Pest test cases present | Run full Pest/browser tests locally |
| Employee directory | EMS official `employee_no`, salary history, active/retired data | EmsService import logic preserved; local CSV import added | Approved EMS export or live EMS endpoint required |
| Attendance | Card registration, four punch events, time zone conversion, kiosk camera/photo, schedule-aware calculations | Existing v6 service, routes and test source inspected | Physical RFID/NFC and camera acceptance |
| Payroll | Half-month periods, real attendance, approved requests and deductions, salary changes, draft/individual release, payslip PDF | V6 logic and existing test source preserved | Approved payroll policy, representative full-period test, statutory settings |
| Leave, requests | Employee forms, admin approvals (non-leave), leave submission to EMS, EMS-approved leave import | v6 request and EMS service retained | EMS leave endpoint and approvals integration contract |
| Reports | Exported reports and private evidence | v6 routes/templates retained | Inspect PDF layout and retention settings |
| System API | `/api/v1`, bearer auth, scoped permissions, hashed tokens, response envelopes, cursor paging, idempotency, OpenAPI | v6 implementation retained; OpenAPI JSON syntax validated | Verify consuming systems, finalize payroll ID format |
| Offline test path | Admin official-roster CSV in **local/testing only**; no browser mock records or automatic seed | New controller PHP-linted and Pest tests authored | Run Pest tests in a configured PHP 8.4/MySQL environment |

## Not yet verified, do not misrepresent

1. **Live EMS data exchange** cannot be marked working without EMS base URL, Bearer token, approved paths/field mappings, and a running EMS service. The provided API standard defines conventions, but not every concrete EMS endpoint. Never invent URLs.
2. **All changed Blade screens in an actual browser** have not been fully exercised in this build's environment. Prior v6 `TESTING.md` results are *inherited* and cannot certify these new UI changes.
3. **MySQL/Pest execution** needs `composer install` and correct PHP extensions in the target environment. This creation environment could lint PHP and JavaScript but could not install the locked vendor packages over the network.
4. This is not a byte-for-byte transfer of every v4 screen. Original design language and major chrome are retained while individual module contents use the v6 Blade forms because v4's inner modules used simulated localStorage state.

## Before integration into ESMS monorepo

Run `composer install`, copy `.env.example` to `.env` *only on a fresh install*, `php artisan key:generate` *only for a fresh install*, confirm `php -m` has `pdo_mysql`, `fileinfo`, `zip` and required extensions, then `php artisan migrate`, `php artisan test` on **a separate test database**, and browser-check login/MFA, employee schedules, attendance, payroll, release, reports, and API calls. Do not run Pest/RefreshDatabase against the real `tap_pms` database. Back up `.env` and private storage; `APP_KEY` must not change on an existing installation.

Check the EMS API contract with EMS team, agree actual endpoints, confirm fields in `docs/openapi.json` across both teams. Use the provided original API standard as the authoritative protocol. Validate full payroll results against HR's accepted policies before any real payslip release.
