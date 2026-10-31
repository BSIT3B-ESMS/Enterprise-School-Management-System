# EMS integration: current source contract versus required School Systems API Standard

Checked 2026-10-30 against public source (not a live deployment):

- https://github.com/yumulniccolo/EMS/blob/main/routes/api.php
- https://github.com/yumulniccolo/EMS/blob/main/app/Http/Controllers/Api/V1/EmployeeController.php
- https://github.com/yumulniccolo/EMS/blob/main/app/Http/Resources/V1/EmployeeResource.php

## Endpoints verifiable in EMS source

The source registers GET employee list, employee details, and employee status, plus colleges, job titles, and rate tiers. It applies `employees:read` or `reference:read` scopes, and Laravel `apiPrefix` is `api/v1`. These are **source-code observations**, not a verified running EMS connection.

## Blocking mismatches to resolve with EMS team

1. The EMS list currently uses `paginate(50)` (offset pages), rather than cursor pagination with `meta.has_more` and `meta.next_cursor` mandated by the shared `API.md`. PMS intentionally rejects a mismatched list envelope instead of silently importing a partial roster.
2. The `EmployeeResource` fields include `employee_no`, `full_name`, `job_title`, `base_rate`, `status`, and `updated_at`. It does **not** expose canonical `salary_effective_on`, a complete salary-history feed, or the agreed `monthly_rate` money string. Its `base_rate` is cast to a float. **Do not assume this is a monthly payroll salary.** The EMS team must confirm the field's meaning/units, official monthly rate, effective-date history, and API representation.
3. The current `routes/api.php` does not expose the necessary leave balance/approval feed or POST leave request endpoint. Thus PMS cannot truthfully claim that leave filing/proof handoff or updated credits are live-connected.
4. To comply with the standard, EMS must document payroll-relevant resources and permissions in its OpenAPI contract, including request-id relationships, idempotency, proof attachments, UTC timestamps and decimal strings.
5. Once both teams sign off, set `EMS_*_PATH` and field mappings in `.env`. Keep `EMS_ENABLED=false` until all required feeds work. If it is enabled, Laravel blocks payslip release unless recent employee and leave sync has succeeded.

## Safe local evaluation

- The existing CSV/JSON import remains **offline testing only** with real, EMS-assigned IDs. It must not be mistaken for real-time live API integration.
- The optional scheduler (`EMS_AUTO_SYNC=true`, plus `php artisan schedule:work` as a separate active process) pulls confirmed EMS endpoints every 15 minutes; it is off by default. It never substitutes for missing endpoints.
- No direct SQL connection to EMS tables, no fabricated employee IDs, no hardcoded tokens, and no fake leave balances.

This document is not a claim of production integration or an agreed final EMS API contract.
