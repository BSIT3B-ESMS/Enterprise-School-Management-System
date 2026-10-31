# EMS handoff — PMS owns work schedules

This document distinguishes implemented PMS behavior from endpoints still needed from EMS. It is based on the supplied school API standard and the EMS source in monorepo commit `1037c7ae08c226e01f3303b79cf47a810a175457`, not a claim of live connectivity.

## Ownership

PMS administrators own work schedules: assigned workdays, start/end time, lunch and grace, with effective-date history. No employee schedule endpoint is requested from EMS. PMS calculates required daily hours from that schedule and actual hours from four attendance punches.

EMS owns employee identity, status, effective salary, leave balances and leave decisions. PMS owns leave filing and sends the original request identifiers/dates/type/reason. EMS returns the decision, official leave ID, final dates and paid/unpaid treatment. PMS never allocates or decrements official EMS balances.

## Existing EMS employee endpoint

`GET /api/v1/employees` exists with `employees:read` in the inspected source. To satisfy the shared standard and PMS:

- Change list pagination to cursor paging, sorted by `updated_at` then a stable ID, accepting `limit` (default 50, max 100), `cursor` and `updated_since`.
- Emit only `data` and `meta` at top level, using `meta.next_cursor` and `meta.has_more`.
- Return `base_rate` as a two-decimal **string**, not a float. Agree that this is the employee's actual monthly basic salary for payroll; if it is only a grade reference rate, expose the actual monthly salary separately. PMS must not assume an FTE/proration rule from a reference rate.
- Include `salary_effective_on` as a date; employee `updated_at` or `hired_on` cannot be substituted for the salary's effective date.
- Retain `employee_no`, `full_name`, `status` and UTC `updated_at`. Job title and college/office display are optional.
- For salary changes, provide dated payroll-relevant history or export its effective rows, oldest first. A single current salary cannot reconstruct past salaries.

PMS field mapping may map `monthly_rate` to `base_rate`, `position` to `job_title` and `department` to `college.name`. This mapping changes names only; it does not convert an unsafe float into compliant money or invent a missing effective date. Do not enable live payroll until these fields are agreed.

## Additional EMS endpoints required

Paths below are proposals for the EMS owner to adopt or rename in its OpenAPI. PMS paths are configurable; they are NOT asserted to exist in the inspected repo.

- `POST /api/v1/leave-requests`: permission `leave-requests:write`; receive PMS `request_id` UUID, `employee_no`, `starts_on`, `ends_on`, `leave_type`, `reason`; optional proof as documented multipart. Honor UUID `Idempotency-Key` and return the correlated request ID in `data`.
- `GET /api/v1/leave-requests`: permission `leave-requests:read`; final approval/rejection/cancellation feed with `leave_id`, original `request_id` for PMS-originated records, `employee_no`, final `starts_on`, `ends_on`, `is_paid` JSON boolean and `status`. Pending submissions do not count as approved leave. Include changes/cancellations so PMS can stop applying obsolete approvals. Dates are sent by PMS initially and returned by EMS as the final decision facts.
- `GET /api/v1/leave-balances`: permission `leave-balances:read`; rows contain `employee_no`, `leave_type`, `remaining_days` as a two-decimal string, `as_of` date and UTC `updated_at`. EMS may compute balances from its service; a dedicated database balance column is not required.

All list endpoints follow the same cursor envelope. Use only the payroll-authorized employee scope. PMS does not need EMS credentials/password hashes, medical details or enrollment course data.

## Configure PMS after EMS implements/signs off

Set server-only host/token, the agreed paths and field mappings in `.env`, then `php artisan config:clear`. Do not expose the EMS token in the browser. Example mappings and JSON schemas are in `EMS-CONNECTION.md` and `ems-required.openapi.json`.

Run employee, approved-leave and balance sync. Compare an employee with HR's source, file a leave from PMS, approve/reject it in EMS, synchronize the result, and reconcile a payroll period before live use. Included automated round-trip tests use controlled HTTP responses, not a real EMS server.
