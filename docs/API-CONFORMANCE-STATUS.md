# TAP-PMS API.md conformance and limits

Source: the team's attached `API(1).md` (School Systems API Standard), checked against this source tree on 2026-10-30. This is a **source-level implementation status**, not a live integration certification. The monorepo's `API-STANDARD.md`, when available, has precedence over the attached guide.

## Implemented in TAP-PMS app-to-app API

| API.md rule | Implementation / evidence | Status |
| --- | --- | --- |
| `/api/v1` URLs | `routes/api.php`; Laravel API route prefix | Implemented |
| Bearer token per calling app | `AppToken` + `IntegrationToken`, `pms:token` command; SHA-256 hash at rest; revocation | Implemented and tested |
| Named permissions | `payroll-periods:read/write`, `payslips:read/write`, denied with 403 | Implemented |
| Missing/revoked token | 401, `WWW-Authenticate: Bearer` | Implemented |
| Response `data` / `meta` | `PayrollApiController`, `ApiResponse`; no success status string | Implemented for API routes |
| Laravel validation errors | HTTP 422 `message` / `errors`; business `code` | Implemented in API endpoints |
| Official employee ID | `employee_no` sourced from EMS; payroll period/payslip UUIDs owned by PMS | Implemented, UUID format still needs inter-team sign-off |
| Cursor paging / `updated_since` | `/payroll-periods`, `/payslips`, cursor + `meta`; max `limit=100`, updated order | Implemented and tested with MySQL 8 |
| Money/UTC | Payroll API emits decimal strings, timestamps as ISO 8601 UTC | Implemented in payroll API |
| POST idempotency | `IdempotentPost` saves token-scoped response 24h; repeat body replays, conflict 409 | Implemented |
| Rate limits, logs | `AppToken` per-token throttle and metadata-only request logging, no bearer logs | Implemented |
| OpenAPI | `docs/openapi.json` documents PMS-owned APIs | Implemented |

## EMS client hardening in this update

- EMS endpoints and field mappings **must be supplied by EMS's actual OpenAPI**. No invented live URLs, salary meanings, leave approvals or credits.
- Employee, leave decision, and leave credit feeds use `data`/`meta` cursor paging, a fixed record/page cap, loop detection, and **all-or-nothing DB import**. Failure does not partially replace the local mirror. Older employee/credit payloads cannot overwrite fresher local EMS facts.
- Employee `employee_no` remains EMS-owned. EMS leave ID cannot be reassigned to a different employee. Leave credits are read-only mirrored data, never derived by deducting submitted PMS leave.
- PMS leave filing stays in the original workflow. Admin's **Send / retry EMS** uses Bearer plus `Idempotency-Key` equal to existing request UUID; verifies `data` object and echoed IDs if present. Employee uploads with proof require an **agreed multipart field** (`EMS_LEAVE_PROOF_FIELD`). Without that field, the upload stays private locally, the request stays pending and **nothing is sent**. This prevents silently losing required evidence.
- Read-only EMS leave credits appear in existing employee leave and admin Appraisals & Leaves pages when the feed has been provided and synchronized. The Appraisals & Leaves section includes a dedicated Refresh EMS leave credits button; original primary navigation and kiosk flow remain unchanged.
- The `/api/v1` API is exclusively system-to-system. Admin/employee ID login and kiosk behavior are not changed by API compliance.

## Blocking agreements / still unverified

1. **EMS must supply confirmed paths, token permissions and OpenAPI schemas** for employee list with current monthly payroll rate + effective salary history, approved/rejected leave decisions, remaining leave balances, leave submissions, and proof attachment field. Do not assume EMS `base_rate` is a monthly payroll salary.
2. **Actual live EMS responses must be checked** for cursor pagination, fields, money-string formatting, JSON envelope, UTC dates, request IDs, and multipart upload. Existing remote EMS may still not provide these endpoints; code cannot invent them.
3. **Official payroll/payslip UUID format sign-off** with the integrating teams is pending.
4. **Runtime testing:** 66 Pest tests / 357 assertions passed on PHP 8.4 and MySQL 8. API permissions, idempotency, cursors and controlled EMS contract responses are tested. A live run against the EMS team’s server remains required.
5. **Full original UI parity** has not been independently acceptance-tested screen by screen on Windows; this patch specifically avoids redesigning existing screens.
6. **Employee payroll policies and deductions** still require HR reconciliation, not just programmatic correctness.

## Operator checks (Windows / PowerShell)

1. Back up the existing `tap_pms` DB, project and `storage`. Do **not** overwrite `.env` or regenerate `APP_KEY`.
2. Apply the patch to an extracted copy of the prior original-workflow Laravel build.
3. Run `php artisan migrate`, then `php artisan optimize:clear`.
4. Run `php artisan pms:api-contract-check` (secrets never displayed).
5. For live EMS, obtain approved endpoint paths/token and set `EMS_*` variables from EMS's OpenAPI. Set `EMS_ENABLED=true` only after the EMS team supplies the required routes. Then `php artisan config:clear`.
6. With a separate test MySQL database, run `php artisan test` (Pest uses a destructive database refresh: **never use the working PMS database**).
7. Manually test actual employee ID login, kiosk automatic AM/PM, camera proof, each request, payroll, payslip release, and EMS leave credits.

**Do not claim 100% integration-ready, production-ready, or successful live EMS sync without those checks.**

## 2026-10-31 ownership confirmation

EMS is the confirmed authority for leave credits and remaining balances, **not TAP-PMS**. The live PMS release guard now requires a successful recent EMS `balances` sync as well as `employees` and `leaves`, and the readiness command treats the EMS balances mapping as required. Changes are source checked; external live compliance still requires a published EMS endpoint and running tests. See `EMS-LEAVE-BALANCE-OWNERSHIP.md`.
