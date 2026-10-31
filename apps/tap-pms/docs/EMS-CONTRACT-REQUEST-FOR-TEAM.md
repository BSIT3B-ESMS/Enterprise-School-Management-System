# Requested EMS API contract for TAP-PMS integration

Please agree and document the following in **EMS's `docs/openapi.json`** before TAP-PMS enables live synchronization. This is a **request to the EMS team**, NOT a claim that these endpoints exist. Use EMS-chosen resource paths, authorization scopes and names consistent with `/api/v1` and the shared School Systems API Standard.

| Information PMS needs | Data owner | Proposed required facts, not fixed route names |
| --- | --- | --- |
| Employee payroll roster | EMS | `employee_no`, `full_name`, `status`, `department`, `position`, `monthly_rate` as `"22000.00"`, `salary_effective_on`, UTC `updated_at`; verified effective salary history (appraisal) |
| Leave decisions | EMS | EMS official `leave_id`, `employee_no`, dates, `status=approved/rejected/cancelled`, boolean `is_paid`, original PMS `request_id` |
| Leave credits | EMS | `employee_no`, leave classification `leave_type`, `remaining_days` as a two-decimal string, `as_of` date, UTC `updated_at` |
| Submit employee leave request | PMS submits / EMS decides | PMS `request_id` (UUID), EMS `employee_no`, dates, type, reason, and proof file if attached; `Idempotency-Key` equal to PMS request UUID |

Every list must return `{ "data": [...], "meta": { "next_cursor": null, "has_more": false } }` with cursor-based pagination (`limit` max 100) and `updated_since` when supported by the resource contract. POST must return a `data` envelope and support idempotent retries. Proof uploads must specify their multipart field name and allowed MIME/size. Confirm separate scoped Bearer permissions. Return UTC ISO 8601 timestamps and official IDs, not database auto-increment IDs. Treat published schemas as the source of truth.

TAP-PMS config supports `EMS_EMPLOYEES_PATH`, `EMS_LEAVES_PATH`, `EMS_BALANCES_PATH`, `EMS_LEAVE_REQUESTS_PATH`, corresponding JSON field mappings, and `EMS_LEAVE_PROOF_FIELD`. Once EMS confirms exact names, map **canonical PMS field -> EMS field** for read feeds, and **EMS field -> canonical PMS field** for write payloads. No third-party fields should be guessed.

Until EMS confirms these, TAP-PMS keeps `EMS_ENABLED=false`, local exports are only for testing, pending leave is not approved leave, and unavailable leave credits are displayed as **not synchronized**, not zero.
