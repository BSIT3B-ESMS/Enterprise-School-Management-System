# Integrations

How other university systems connect to the Online Admission System (OAS). Each interface is built and tested; the ones marked **waiting** are switched off or unused until the other system is ready. Nothing in the OAS needs to change to connect them beyond what each section says.

## Common rules

- **Base URL:** `https://<oas-host>/api/integrations/...`, JSON in and out.
- **Authentication:** every call sends `X-API-Key: <key>`. The sysadmin creates one key per system on the server:
  ```
  php artisan api-client:create employment --ips=10.0.5.20   # --ips optional; Nginx can filter too
  ```
  The key is shown once. Only its SHA-256 is stored. A key works only for its own system's endpoints.
- **Expiry (NFR-05):** keys expire after 90 days (`API_KEY_DAYS`). Running the same command again issues a new key (rotation); the old one stops working at once. An expired key gets `401` with *"This API key has expired…"*.
- **Disable:** `php artisan api-client:create <name> --disable`.
- **Errors:** `401` bad/expired key or IP; `422` with `{ message, errors }` for invalid data; `404` unknown record; `429` rate limit (240 requests/minute per key).
- **Audit (NFR-09):** every inbound call is written to the audit log as *"API call from <system>: METHOD /path → status"*, plus the change it made (with before/after values).
- **Idempotent:** sending the same data twice changes nothing, so a caller can always retry.

| ID | System | Direction | Status | Section |
|---|---|---|---|---|
| INT-A | Employment System (staff accounts) | Employment → OAS | Built, **waiting** for the Employment System | [1](#1-employment-system-staff-accounts-fr-30) |
| INT-B | Registrar / Enrollment (admitted applicants) | Registrar ← OAS | Built (API + CSV/XLSX export) | [2](#2-registrar-admitted-applicants-fr-29) |
| INT-C | Enrollment (student number back) | Enrollment → OAS | Built, off (`ENROLLMENT_CALLBACK_ENABLED`) | [3](#3-enrollment-student-number-callback) |
| INT-D | Document Management System (DMS) | OAS → DMS | Built, **waiting** for the DMS API (`DMS_DRIVER=fake`) | [4](#4-document-management-system-dms) |
| INT-E | SMS provider | OAS → provider | Built, off (`SMS_DRIVER=none`) | [5](#5-sms-provider-fr-13) |
| INT-F | Email (Gmail SMTP, Brevo failover) | OAS → provider | Live | `.env` `MAIL_*` |
| INT-G | CAPTCHA (Cloudflare Turnstile or Google reCAPTCHA) | OAS → provider | Built, off (`CAPTCHA_DRIVER=none`) | [6](#6-captcha-on-registration-nfr-06) |

---

## 1. Employment System: staff accounts (FR-30)

Every OAS user except applicants comes from the Employment System. It sends the employees who work in admissions, each with the OAS role they should have; the OAS creates, updates or disables their accounts. Until it is connected, the sysadmin uses `php artisan user:create`, and two demo accounts exist (see PROGRESS.md).

API client name: **`employment`**.

### Roles the Employment System may give

| `role` | Who | Can |
|---|---|---|
| `admission` | Admission Staff (evaluators) | All office work except releasing results |
| `admission_head` | Admission Head / Director | Everything Admission Staff do, plus release results (FR-20) |
| `proctor` | Exam proctors / venue staff | Rosters, QR check-in, attendance, envelope receipt |
| `clinic` | University clinic (Medical) | Medical requirements only (clinic portal) |
| `read_only` | Deans, Registrar, DPO / auditors | View the office panel and reports; change nothing |
| `college_dean` | College deans (needs `college`) | Interviews for their college; their college's reports |

`sysadmin` accounts are never managed from outside (server only). An email that belongs to an applicant account is refused.

### `POST /api/integrations/employment/employees`

Create or update up to 500 employees per call. Matched by `employeeId`, then by email (so accounts made by hand are linked the first time).

```json
{
  "employees": [
    { "employeeId": "E-1001", "name": "Rosa Dizon", "email": "r.dizon@plpasig.edu.ph", "role": "admission", "active": true },
    { "employeeId": "E-2040", "name": "Dr. Ana Lim", "email": "a.lim@plpasig.edu.ph", "role": "college_dean", "college": "CON" }
  ]
}
```

Response `200` (each employee succeeds or fails on its own; `422` only if all failed):

```json
{ "data": [
  { "employeeId": "E-1001", "status": "created" },
  { "employeeId": "E-2040", "status": "rejected", "message": "A college_dean needs a college code: CAS, CBA, CCS, CHM, COE, COED, CON." }
] }
```

`status`: `created`, `updated` (role, name, email, college or active changed; audited with before/after), `unchanged`, `rejected`.

New accounts get a random password: the person signs in after **Forgot password**, or through single sign-on once that is added.

### `DELETE /api/integrations/employment/employees/{employeeId}`

The employee left or moved: the account is disabled at once (its open sessions stop working on the next request). Accounts are never deleted, because the audit trail points to them. Send the employee again with `"active": true` to re-enable.

### `GET /api/integrations/employment/employees`

Every linked account, for reconciliation: `employeeId, name, email, role, college, active, source, lastLoginAt`.

**Suggested schedule:** call `POST` whenever an assignment changes, plus a nightly full sync of everyone in admissions.

---

## 2. Registrar: admitted applicants (FR-29)

API client name: **`registrar`**. The same data is downloadable by staff as the **Admitted applicants (Registrar)** report (`/api/reports/admitted-applicants?format=csv|xlsx|pdf`).

### `GET /api/integrations/registrar/admitted?since=2027-03-01T00:00:00Z&page=1`

Admitted applicants of the current admission period, oldest admission first, 500 per page. `since` (optional) returns only those admitted at or after that time, for incremental pulls.

```json
{
  "data": [{
    "applicationNumber": "AD-2026-00001", "lrn": "123456789012",
    "lastName": "Mendoza", "firstName": "Jordan", "middleName": "Reyes", "sex": "Male", "birthday": "2008-03-14",
    "email": "jordan@example.com", "phone": "+63 917 123 4567", "address": "12 Mabini St.",
    "pasigResident": true, "district": 1, "barangay": "Kapitolyo",
    "lastSchool": "Rizal High School", "schoolType": "Public", "strand": "STEM",
    "programCode": "BSIT", "program": "BS Information Technology",
    "admittedAt": "2027-03-10", "studentNumber": null
  }],
  "meta": { "page": 1, "pages": 1, "total": 1, "columns": { "applicationNumber": "Application no.", "...": "..." } }
}
```

Exam scores never leave the OAS. The column list lives in `App\Services\RegistrarExportService::COLUMNS`; change it there when the Registrar confirms its layout.

---

## 3. Enrollment: student number callback

API client name: **`enrollment`**. Off until `ENROLLMENT_CALLBACK_ENABLED=true` (returns `404` while off).

`POST /api/integrations/enrollment/student-number` with `{ "admission_reference": "AD-2026-00001", "student_number": "2027-00123" }`. Safe to repeat.

---

## 4. Document Management System (DMS)

The OAS sends to the DMS; the DMS doesn't call the OAS.

- **Records (`DMS_DRIVER`):** every record change (applicant data, document uploaded/reviewed, exam scheduled, result, offer, admission completed, retention expired) goes into the `dms_outbox` table and is delivered by a queued job with an `Idempotency-Key`. A DMS outage never blocks staff work; `php artisan dms:outbox --retry` (hourly) resends. `fake` (default) marks them delivered locally; `http` sends to `DMS_BASE_URL` with `DMS_API_KEY`.
- **Files (`DOCUMENT_STORE`):** `local` (private disk, default) or `dms` (uploaded to the DMS; the OAS keeps only the DMS document ID). Files already stored stay readable from where they were saved.
- **To connect:** get the DMS API contract, adjust `App\Services\Dms\HttpDmsClient` and `App\Services\Documents\DmsDocumentStore` to it, set the `.env` values, then run `php artisan dms:outbox --retry` to deliver what is waiting (and `--resync=AD-2026-00001` to send one applicant's record again).

---

## 5. SMS provider (FR-13)

`SMS_DRIVER=none` (default), `log` (written to the log, for testing) or `http`. With `http`, each exam schedule, exam reminder and result-release notice also goes by SMS:

```
POST {SMS_URL}
X-API-Key: {SMS_API_KEY}
{ "to": "+639171234567", "message": "PLP Admissions: Your entrance exam is on …", "sender": "{SMS_SENDER}" }
```

Adjust the body in `App\Notifications\SmsChannel` to the provider chosen. Sent from the queue; a failure is retried and never blocks the email or in-app notice.

---

## 6. CAPTCHA on registration (NFR-06)

Set `CAPTCHA_DRIVER=turnstile` (Cloudflare, free) or `recaptcha` (Google), with `CAPTCHA_SITE_KEY` and `CAPTCHA_SECRET`, then `php artisan optimize`. The **Create account** form then shows the widget and the server checks its token with the provider; if the provider can't be reached, registration is refused (fails closed).
