# EMS connection

EMS owns employee identity, employment status, effective salary, leave decisions, and official remaining leave credits. PMS reads approved facts and never writes to the EMS database. A filed PMS leave request can be sent to an agreed EMS POST endpoint; approval is returned through the leave feed. Proof files remain private in PMS; remote attachment transfer requires a separately agreed EMS file interface.

The real EMS host, credentials and endpoint mappings were not supplied. No working external endpoint is claimed. Set these in `.env` from the EMS team's OpenAPI contract:

- `EMS_ENABLED=true`, `EMS_BASE_URL=https://<agreed-host>`, `EMS_API_TOKEN=<server-only-token>`.
- `EMS_EMPLOYEES_PATH`, `EMS_LEAVES_PATH`, `EMS_BALANCES_PATH`, `EMS_LEAVE_REQUESTS_PATH`: agreed relative `/api/v1/...` paths.
- `EMS_EMPLOYEE_FIELDS`: JSON mapping canonical PMS field names to EMS response field paths.
- `EMS_LEAVE_FIELDS`: same mapping direction for approved leave.
- `EMS_BALANCE_FIELDS`: JSON mapping canonical PMS leave-credit fields to EMS response fields.
- `EMS_LEAVE_REQUEST_FIELDS`: JSON mapping remote POST field names to PMS canonical request fields.
- `EMS_LEAVE_PROOF_FIELD`: the multipart field name for a proof file **only after EMS confirms it in OpenAPI**. An employee proof will block submission when this setting is absent, so it cannot silently disappear.

For example, if the agreed EMS response really uses the same field names, employee mapping would be:

```dotenv
EMS_EMPLOYEE_FIELDS='{"employee_no":"employee_no","full_name":"full_name","monthly_rate":"monthly_rate","status":"status","salary_effective_on":"salary_effective_on","updated_at":"updated_at","department":"department","position":"position"}'
EMS_BALANCE_FIELDS='{"employee_no":"employee_no","leave_type":"leave_type","remaining_days":"remaining_days","as_of":"as_of","updated_at":"updated_at"}'
EMS_LEAVE_FIELDS='{"leave_id":"leave_id","employee_no":"employee_no","starts_on":"starts_on","ends_on":"ends_on","is_paid":"is_paid","status":"status","request_id":"request_id"}'
EMS_LEAVE_REQUEST_FIELDS='{"request_id":"request_id","employee_no":"employee_no","starts_on":"starts_on","ends_on":"ends_on","leave_type":"leave_type","reason":"reason"}'
```

These are examples, not a statement of their deployed schema. Adjust to the signed contract. Run `php artisan config:clear` after changes. Use Settings to synchronize employees first, then leave. HTTP is permitted only for localhost; remote EMS uses HTTPS. Pagination follows `data` and `meta.has_more` / `meta.next_cursor`. Retry delays are 1, 4 and 16 seconds for connection failures, 429 and 5xx. Failed validation preserves the existing mirror and records a failed sync. When EMS_AUTO_SYNC=true and an external Laravel scheduler process is running, the app schedules employee/leave synchronization every 15 minutes and leave credits when its required mapping is configured. Otherwise, admins synchronize manually. Payroll release requires recent employee, approved-leave, and **leave-balance** synchronization in live mode; credits are read-only and are never invented or subtracted by PMS.

## Operator canonical JSON import

For local evaluation or a validated EMS export, save a JSON array outside the public web directory. Example employee:

```json
[{"employee_no":"26-00001","full_name":"Example Employee","department":"Example Department","position":"Example Position","monthly_rate":"22000.00","status":"active","salary_effective_on":"2026-01-01","updated_at":"2026-10-30T00:00:00Z"}]
```

Replace examples with official EMS values. Accepted employee statuses: `active`, `on_leave`, `absent`, `inactive`, `retired`, `terminated`, `resigned`. Use decimal strings for money. Supply effective salary history as separate employee rows with the appropriate effective dates, oldest first.

```bat
php artisan pms:import-ems employees path/to/employees.json
php artisan pms:import-ems leaves path/to/leaves.json
```

Example leave array:

```json
[{"leave_id":"EMS-EXAMPLE-001","employee_no":"26-00001","starts_on":"2026-11-02","ends_on":"2026-11-03","is_paid":true,"status":"approved"}]
```

For local testing, official leave credits can be imported using `php artisan pms:import-ems balances file.json` after applying the new database migration. Credit row: `{"employee_no":"26-00001","leave_type":"vacation_leave","remaining_days":"7.50","as_of":"2026-10-30","updated_at":"2026-10-30T00:00:00Z"}`. This is **not** a substitute for live EMS integration.

`is_paid` must be a JSON boolean. Leave statuses: `approved`, `rejected`, `cancelled`. An optional PMS `request_id` UUID links the EMS decision to the originating request. Omitted records are not assumed deleted or inactive; EMS must send explicit status changes. Imports are transactional. Operator file imports do not count as a successful remote synchronization for the enabled-EMS release gate.


## Ownership and inspected EMS compatibility

Working schedules are managed in PMS, not requested from EMS. Employee-required hours are derived from the assigned PMS schedule, excluding configured lunch; actual worked hours come from kiosk punches.

The shared standard requires cursor paging and money strings. The inspected EMS employee endpoint currently has page-based responses, a numeric `base_rate`, and no salary-effective date in its employee resource. A naming map alone cannot fix those semantic differences. Use `EMS-TEAM-REQUIREMENTS.md` for the exact upstream work and `ems-required.openapi.json` as the proposed external contract. Do not substitute employee `updated_at` or `hired_on` for salary effective date.

Recommended least-privilege permissions to request from EMS: `employees:read`, `leave-requests:write`, `leave-requests:read`, `leave-balances:read`. Final permission names belong to EMS. No EMS password hashes or restricted medical fields are required by PMS.

When EMS adopts `base_rate` as an actual monthly salary string, the employee mapping can be:

```dotenv
EMS_EMPLOYEE_FIELDS='{"employee_no":"employee_no","full_name":"full_name","monthly_rate":"base_rate","status":"status","salary_effective_on":"salary_effective_on","updated_at":"updated_at","position":"job_title","department":"college.name"}'
```

Map approved final leave dates even though the original requested dates came from PMS. The returned dates confirm what EMS approved. Matching the original `request_id` resolves the pending PMS request; EMS owns its separate `leave_id`. Remaining balance is shown from EMS and never locally decremented. Multipart proofs now use the correct multipart Content-Type with the generated boundary; no JSON Content-Type is carried into that upload.
