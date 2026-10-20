# EMS Agent Guide (PLP Employee Management System)

Dropdown guide for agents working on this repo. Open only the sections you need. Source of truth: the Software Design Document (SDD) excerpts the owner pasted on 2026-10-20, plus the current prototype (commit `be1cfa4`).

**Terminology rule (owner decision):** the word **Department** becomes **College** everywhere (UI text, routes, tables, API fields, tests). PLP organizes by Colleges, and each College will later have **Programs** (seeded list in the decision log below).

<details>
<summary><strong>00. Decision log (owner answers, 2026-10-20). Overrides anything below that disagrees</strong></summary>

**Organization and people**
- `colleges`: the 7 academic colleges. Non-teaching units live in a **separate `offices` table** (seed list, owner-edited: Human Resources, Management Information System (MIS), Finance & Accounting, Student Success Office (SSO)). Marketing & Communications is removed.
- An employee has `college_id` (teaching, nullable) and `office_id` (non-teaching home, nullable); **at least one is required**. A person based in an office (for example Finance) can also teach, so both can be set. One primary `job_title_id`.
- **Job titles are per college (or per office)**, not shared. Each links to a rate tier.
- Employees page has two tabs, **Faculty** and **Non-Teaching Staff**, with two create forms (same `employees` table). Seed: the 126 faculty plus **5** non-teaching staff.
- **Built in Phase 1 (differs from the table in section 4.2):** `rate_tiers` holds one row per (salary_grade, step, effective_date). A job title stores only `salary_grade`; an employee stores only `salary_step`. The base rate is looked up (`Employee::baseRate()`), so a rate change applies to everyone automatically. Government IDs live in one encrypted `government_ids` column plus a `government_id_hints` json (last four digits). `college_transfers` has `applied_at` for future-dated transfers. Salary figures and grades are marked VERIFY in the seeders.
- **Built in Phase 2 (staff authentication and roles):** Laravel Fortify handles sign-in, sign-out, throttling (5 per minute), the two-factor challenge, and password confirmation; registration, password reset, and passkeys are off. Middleware groups: `account` (signed in, active, not idle) and `staff` (adds temporary-password and HR two-factor checks). Routes are gated by `role:` middleware: HR pages for HR Head and HR Staff; reports, the archive, Audit Logs, and Accounts for HR Head only; the Employee interface for the Employee role only. HR Head account administration lives in `App\Actions\Accounts\*` with `UserPolicy`; at least two active HR Heads must remain (`config/ems.php`). Settings are in `config/ems.php` (`EMS_REQUIRE_MFA`, `EMS_CHECK_BREACHED_PASSWORDS`). **The authenticator requirement is paused by the owner (email and password only for now): `EMS_REQUIRE_MFA` defaults to false. Set it to true to bring it back; no code change is needed.** The page contents still come from the browser mock data, and applicant login is still the mock sign-in until Phase 6. The shared login form hands any email that is not a mock applicant account to the server.
- **Built in Phase 3 (staff core):** Employees (Faculty and Non-Teaching tabs, search and filters on the server, profile, add, edit, archive and restore, never delete), Colleges, Offices, Job Titles, and Pay Rates now read the database. A rate change never edits an old rate: it adds eight new rates from an effective date (`RateTier::scheduleOn()`, `currentFor()`). The HR Head alone transfers an employee (job title, college, or office; recorded in `college_transfers`, future-dated allowed), changes a salary step, archives or restores, reveals a government ID (a reason from `RevealGovernmentId::REASONS`, audited), manages reference data, and exports reports (each export is logged first through `exports.store`). Future-dated transfers are applied by `php artisan transfers:apply`, scheduled daily at 00:05 in `routes/console.php`; the server needs one scheduled task that runs `php artisan schedule:run` every minute (Windows Task Scheduler). The faculty roster comes from the owner's list (`FacultyRosterSeeder`; names only, everything else blank for HR to complete); five college deans are `college_deans` rows. **Still on the browser sample data:** Dashboard figures, Recruitment, Calendar, Audit Logs, Open Job Positions, the Careers website, and the Employee and Applicant interfaces. The old scripts for the replaced pages (`employees.js`, `departments.js`, `job-titles.js`, and others) are no longer loaded but not yet deleted.
- Employee number: `EMP-1001` style, copied from the mockup (`EMP-` plus one running 4-digit counter starting at 1001, never reused). The existing route constraint `EMP-[0-9]+` stays.
- Programs seeded (employees link to a college only for now):
  - CCS: BSCS, BSIT
  - COE: BSECE
  - CON: BSN
  - CIHM: BSHM
  - CAS: AB Psy
  - COED: BEEd (Bachelor of Elementary Education), BSED English, BSED Filipino, BSED Math
  - CBA: BSA, BSBA (Major in Marketing Management), BS Entrep
- Government IDs stored: TIN, GSIS, SSS, PhilHealth, Pag-IBIG, BIR number. **Write-only on create, masked on read** (last 4). HR Staff can enter but never reveal. Reveal is HR Head only, needs a reason, and is audited.
- Documents only (no structured education/experience tables). Allowed: PDF, JPG, PNG, max 5 MB, private disk, random stored names.
- Employment status: `active`, `inactive` (for example MIA or unspecified), `resigned`, `retired`, `terminated`. `status_reason` and `status_effective_on` are nullable.
- **Absent is not an EMS concept.** Attendance and payroll own it. Effective status for the API is `active`, `on_leave`, `inactive`, plus `status_detail` (resigned/retired/terminated/null).

**Auth and access**
- Applicants: **public self-registration stays** (Terms first). The temporary account lets them submit documents, see their status and schedule. HR also contacts them outside the system. The system sends no email.
- Seeded HR Heads (**local/testing/staging only, never production**): Niccolo Franco Yumul (`yumul_niccolofranco@plpasig.edu.ph`) and Prespurt James Bellen (`bellen_prespurtjames@plpasig.edu.ph`). The dev seeder stores the password `password` directly (seeders skip validation, so the 12-character rule applies only when a user sets a new password). Remove before deployment.
- Staff login/official email layout: `surname_givenname@plpasig.edu.ph` (all given names joined, lowercase, no spaces; numeric suffix on a clash).
- MFA: Laravel Fortify (TOTP + recovery codes), required for HR roles. Breached-password check: Laravel's built-in `Password::uncompromised()`.
- Sessions: idle timeout **15 min HR, 30 min staff**. Login limit 5 attempts/min per account + IP. Failed attempts logged without passwords. HttpOnly, SameSite=Lax, Secure on HTTPS, session ID regenerated at login.
- Staff self-edit: marital status, emergency contacts, personal phone, home address, password. No email; "Forgot Password?" says contact HR.
- **College Dean** is an assignment record (not a role): strictly read-only: own-college profiles (no salary) and config. A Dean cannot approve leave.

**Leave**
- Employees see their own credits: total, used, pending, remaining. Credits are HR reference data, not payroll.
- Approval by **HR only** (HR Head and HR Staff), for employees and Deans alike; employees may cancel pending requests; a rejection reason is required. History is append-only.
- Policy table is keyed by leave type, employment type and optional job title, so rank-based overrides are possible.

**Transfers and rates**
- Transfers can be future-dated; college, office or title changes are all logged; the reason is optional. Table name `college_transfers` (approved) with office columns.
- A rate change applies to everyone on that tier from its effective date; old rows are kept.

**Other**
- Keep the Calendar and interview scheduling. Exports and reports are HR Head only and logged.
- API: read-only; consumers are external systems like Payroll; tokens issued by a developer Artisan command; 50 per page; 60 requests/min; yearly rotation; `full_name`, `hired_on` and current base rate allowed.
- Privacy: placeholder DRAFT notice until the DPO supplies text; `data_requests` table now, UI later; retention is flag-only (`retention_review_at`); no separate DPO/auditor role in v1 (role enum stays extensible).
- Timezone `Asia/Manila`; tests on MySQL; develop on PHP 8.4; mock data discarded; one branch and PR per phase; frontend may change during cutover.

</details>

<details>
<summary><strong>0. Read first: current state vs. target, and decisions still open</strong></summary>

**Current state:** a frontend-only prototype. All data is in `resources/js/mock-data.js` (localStorage). All routes are `Route::view`. No domain tables, no real auth. See `ems-project-state` in memory.

**Target:** the SDD's 3-tier Laravel app: Bootstrap/jQuery presentation, Laravel application layer (web + API gateways), MySQL via Eloquent. The prototype's function signatures in `mock-data.js` are the behavioral spec for the backend. Keep their return shapes (`{ ok, error, errors }`) so page modules change minimally.

**Where the SDD and the prototype disagree (follow the "Decision" column; items marked OPEN need the owner):**

| Topic | Prototype | SDD | Decision |
|---|---|---|---|
| Roles | one implicit HR user | Admin/Staff in some places, HR Head / HR Staff / Employee in others | Use **hr_head, hr_staff, employee** on `users`. Applicants are a **separate guard and table**. External systems use API tokens, not users. |
| Org unit | 14 fake "departments" (incl. offices such as HR, IT, Finance) | Departments (college, administrative, office) | Rename to **colleges**. Non-teaching units go in a separate **`offices`** table (decision log). |
| Self-service edits | civil status, phone, address | marital status + emergency contacts + password only | Owner decision: marital status, emergency contacts, personal phone, home address, password (decision log). |
| Status flags | Active / On Leave (derived) | Active, Inactive, Absent, On Leave (scope lists only three) | Three effective values: active, on_leave, inactive. **Absent is not an EMS concept** (the Attendance system owns it). |
| Hiring stages | Applied, Demo Interview, Interview, Offered, Hired, Rejected | Applied, Interviewing, Hired, Rejected | Store the SDD `stage` (4 values) plus a granular `substage` (demo_interview, interview, offered). API and reports use `stage`. HR Staff may move up to Interviewing only; **Offer and Hire are HR Head only**. |
| Salary | stored per employee and per job-title range | rate tiers are reference only, no computation | Employees point to a **rate tier** (grade, step, monthly amount, effective date). No payroll math anywhere. |
| Audit | free-text entity string in localStorage | structured, append-only, old/new values, IP, user | Build real `audit_logs` (see section 4). |
| Transfers | none | append-only `department_transfers` | Build as **`college_transfers`** (rename approved), with office columns too. |
| Password minimum | 8 chars | 12 chars + breached check | Use the SDD's 12-char policy; update JS rules and server validation together. |
| Applicant per position | one account, many applications | one applicant, one position | Keep account to applications as 1-to-N (matches the prototype and the "one application per email per position" rule). |
| Leave | Filed / Pending / Approved | approve **or reject** | Add Rejected. Leave rows are append-only; changes add history rows. |

</details>

<details>
<summary><strong>1. Purpose</strong></summary>

The EMS is the **single source of truth** for PLP faculty and staff: profiles ("digital resume"), college and job-title assignments, base salary reference tiers, leave requests, and recruitment (applicant to employee). It also exposes read-only JSON so external school systems (Payroll, Attendance) consume this data instead of keeping copies.

It exists so that HR has one audited record per person and other systems never duplicate it.

</details>

<details>
<summary><strong>2. Boundaries</strong></summary>

**Out of scope (never build):** attendance (time-in/out, biometrics), payroll or payslips or tax/deduction math, student/LMS/SIS modules, native mobile apps (responsive web only).

**Must not:**
- Let external systems or browsers touch MySQL directly. All access goes through Tier 2 (`routes/web.php` for sessions and CSRF, `routes/api.php` for stateless bearer-token JSON).
- Let UI code bypass server validation. Client JS validates for convenience; the server is authoritative.
- Return restricted fields from the API (password hashes, government IDs, marital status, emergency contacts, applicant resumes/notes, raw audit logs).
- Overwrite history: transfers and approved leave are append-only.
- Put real employee data in dev/test/staging. Synthetic only.

**Layers and dependencies:**
- `resources/views`, `resources/js`, `resources/scss`: presentation. Follow `.ai/rules/frontend.md`.
- `app/Http/Controllers` + `routes/`: gateways. Thin; call services/actions.
- `app/Models`, `database/`: persistence (Eloquent, migrations, factories, seeders).
- `resources/js/mock-data.js` is **temporary**; it is replaced page by page and finally deleted. Do not add new features to it once its backend equivalent exists.

</details>

<details>
<summary><strong>3. Key files and entry points</strong></summary>

| File | Why read it |
|---|---|
| `AGENTS.md`, `.ai/guidelines/tech-stack.md`, `.ai/rules/*` | Mandatory project rules (read the matching rule file before editing). |
| `routes/web.php` | Every current page route and its comment explaining its JS module. |
| `resources/js/mock-data.js` | The behavioral spec: validation, workflow, logging, derived values. |
| `resources/js/recruitment-workflow.js` | Pure recruitment-stage rules; port to PHP (an enum or state class) with the same semantics. |
| `resources/js/calendar-events.js` + calendar section of `mock-data.js` | Event building and conflict rules (same venue or same applicant overlaps). |
| `resources/js/validation-rules.js` | Field rules (PH mobile, SSS/PhilHealth/Pag-IBIG/TIN digit counts); mirror in Form Requests. |
| `resources/js/employee-list.js` | Search/filter/sort/URL-state model shared by list and report pages. |
| `resources/js/report-generator.js`, `xlsx.js` | PDF/CSV/Excel export; exports must become logged and HR-Head-only. |
| `resources/views/layouts/{admin,employee,applicant,public,guest}.blade.php` | The four portal shells plus login. |
| `resources/views/components/*` | Reuse before writing new markup (modals, forms, report parts, icons). |
| `app/Models/User.php`, `bootstrap/app.php` | Only backend files today; `bootstrap/app.php` has no `api:` routing yet. |
| `tests/Feature/*` | Markup-level Pest tests; extend the pattern for new routes. |

</details>

<details>
<summary><strong>4. Data and contracts</strong></summary>

### 4.1 Faculty headcount to seed (note only; do not prioritize yet)

Owner-supplied current headcount. Seed with **synthetic** people matching these counts. FT = full-time, PT = part-time.

| College | Code | FT | PT | Total |
|---|---|---|---|---|
| College of Education | COED | 10 | 16 | 26 |
| College of Engineering | COE | 7 | 5 | 12 |
| College of International Hospitality Management | CIHM | 8 | 9 | 17 |
| College of Computer Studies | CCS | 12 | 14 | 26 |
| College of Arts and Sciences | CAS | 8 | 6 | 14 |
| College of Business and Accountancy | CBA | 10 | 5 | 15 |
| College of Nursing | CON | 10 | 6 | 16 |
| **Total** | | **65** | **61** | **126** |

Notes: this replaces the prototype's 148-person sample roster and 8 fake teaching departments. `employment_type` (`full_time` / `part_time`) lives on **employees**, not only on job titles. Staff (non-teaching) counts were not given.

### 4.2 Schema outline (API-ready, 3NF)

Conventions: `id` bigint PK for internal joins; stable external identifier `employee_no` (`EMP-1001` style, unique, never reused) for APIs; `created_at`/`updated_at` everywhere; money as `decimal(12,2)`; enums as PHP backed enums; foreign keys with `restrictOnDelete`; **no hard deletes** on people (use status/`deactivated_at`).

| Table | Key columns | Notes |
|---|---|---|
| `users` | id, employee_id (nullable FK), email (unique), password (bcrypt, rounds 12), role (`hr_head`/`hr_staff`/`employee`), is_active, must_change_password, temp_password_expires_at, mfa_secret (encrypted), mfa_confirmed_at, last_login_at | Staff guard. TOTP required for HR roles. |
| `applicant_accounts` | id, name, email (unique), password, phone, profile/201 fields, created_at | Separate guard. Disabled (not deleted) on conversion. |
| `applications` | id, applicant_account_id, job_title_id, college_id, stage (`applied`/`interviewing`/`hired`/`rejected`), substage, offer_response, decided_at, employee_id (nullable), consent_at | One per account per job title. `decided_at` drives the 1-year retention rule. |
| `application_stage_history` | id, application_id, from_stage/substage, to_stage/substage, changed_by, created_at | Append-only. Replaces the prototype's `milestones`. |
| `application_activities` | id, application_id, type (`demo_interview`/`interview`/`other`), date, start, end, venue, status, notes | Interview scheduling and conflicts. |
| `application_activity_changes` | id, activity_id, from/to date and time, reason, reason_detail, changed_by | Append-only reschedule history. |
| `application_drafts` | id, applicant_account_id, job_title_id, payload (json) | Not visible to HR. |
| `colleges` | id, code (COED, COE, CIHM, CCS, CAS, CBA, CON), name, head_employee_id (nullable), is_active | Replaces departments. Non-teaching units live in `offices` (decision log). |
| `programs` | id, college_id, name, is_active | **Stub only.** Owner supplies the list later. |
| `job_titles` | id, title, category (`faculty`/`non_teaching`), college_id (nullable = institution-wide), rate_tier_id, is_active, openings, posting content (description, responsibilities, qualifications, requirements as json), is_published | Publishing drives the public Careers page. |
| `rate_tiers` | id, grade/tier, step, monthly_base_amount, effective_date, is_current | **Reference value only.** Changes add a new effective-dated row; old rows stay. |
| `employees` | id, employee_no, user-facing name fields, birthdate, sex, marital_status, employment_type, hired_on, college_id (nullable, teaching), office_id (nullable, non-teaching; at least one of the two), job_title_id, rate_tier_id (grade + step), official_email, personal email/phone/address, status (`active`/`inactive`/`resigned`/`retired`/`terminated`), status_reason (nullable), status_effective_on (nullable), government IDs (encrypted casts) + `*_last4` for masked display, deactivated_at | Restricted fields encrypted; masked in UI; reveal is audited. |
| `emergency_contacts` | id, employee_id, name, relationship, phone, address | Employee may edit own. |
| `college_transfers` | id, employee_id, from/to college, from/to job_title, effective_date, reason, recorded_by, created_at | **Append-only.** Written whenever college changes, even if the title is unchanged. |
| `employee_documents` | id, employee_id, category (8 existing 201 categories), original_name, size, stored_path (private disk), uploaded_by, created_at | The prototype stored metadata only; real files need private storage and a review of file-upload security. |
| `leave_requests` | id, employee_id, type, start_date, end_date, status (`filed`/`pending`/`approved`/`rejected`), filed_at | Never edited in place after a decision. |
| `leave_status_history` | id, leave_request_id, from/to status, decided_by, note, created_at | Append-only. |
| `calendar_events` | id, title, type, date, start, end, venue, status, notes | HR-created events. |
| `audit_logs` | id, user_id (nullable), actor_type, action (`create`/`update`/`delete`/`login`/`logout`/`export`/`reveal`), entity_type, entity_id, changes (json old/new), ip_address, created_at | Append-only; no update/delete in app code. Reading a masked field logs `reveal`. |
| `api_consumers` | id, name, token_hash, abilities (json), is_active, last_used_at, dpo_agreement_ref | Hashed tokens; the raw token is shown once; never logged. |
| `api_access_logs` | id, consumer_id, route, status, ip, created_at | No tokens or payloads. |
| `lookup_options` / settings | key, value | Dynamic dropdown values HR Head can manage. |

**Derived effective status (computed, never stored):** `inactive` if `employees.status` is not `active`, else `on_leave` if an approved leave covers today, else `active`. (`Absent` belongs to the Attendance system.)

### 4.3 External API (INT-01), read-only

`routes/api.php`, prefix `/api/v1`, Bearer token, JSON, GET only, rate-limited (429), 401 on bad credentials, 403 on insufficient ability.

| Route | Returns |
|---|---|
| `GET /employees` (`?college=&job_title=&status=&updated_since=&page=`) | Paginated list. |
| `GET /employees/{employee_no}` | One record. |
| `GET /employees/{employee_no}/status` | Effective status flag only. |
| `GET /colleges`, `/job-titles`, `/rate-tiers` | Reference data. |

Allowed employee fields: `employee_no`, `full_name`, `employment_type`, `college` (code and name), `job_title`, `base_rate` (from rate tier), `status` (`active`/`on_leave`/`inactive`), `status_detail` (resigned/retired/terminated/null), `hired_on`, `updated_at`. Owner approved these fields; the DPO still confirms. Use Eloquent API Resources with an explicit whitelist so a new column can never leak by default.

### 4.4 Internal AJAX and form contracts

- INT-02: `GET` directory search (`q`, `college`, `job_title`) returning JSON summaries for logged-in staff; internal-class fields only.
- INT-03: `POST/PUT/DELETE` forms with CSRF for profile, transfers, leave, settings; Form Requests validate; policies authorize at route **and** record level.

### 4.5 Invariants that must always hold

1. Every create/update/delete/export/reveal writes `audit_logs`, in the same DB transaction as the change.
2. Changing an employee's college inserts a `college_transfers` row in the same transaction.
3. Applicant-to-employee conversion is one transaction: create user + employee, link the application, disable the applicant login. Roll back on any failure. HR Head only.
4. At least two active HR Head accounts exist; an HR Head cannot deactivate themselves or the last one.
5. HR Staff: no deletes, no role/account changes, no password resets for HR accounts, no conversion, read-only on rates/colleges/job titles, pipeline only up to Interviewing.
6. Employees see only their own record; the directory shows only name, college, job title, official email, office phone.
7. The API never returns restricted data.
8. `rate_tiers` and `college_transfers` are never updated in place.
9. One application per email per job title; closed positions accept no applications; filling a position reduces `openings`.
10. No payroll or attendance logic anywhere.

### 4.6 Data classes (drive masking and exposure)

Restricted (HR only, encrypted, masked, reveal audited, never in API): password hashes, TIN/GSIS/PhilHealth/Pag-IBIG, base rate, marital status, birthdate, emergency contacts, resumes/interview notes. Confidential (role-scoped): home address, personal phone/email, status, transfer history, audit logs. Internal (any signed-in staff): name, college, job title, official email, office phone. Public: college list, published job openings.

### 4.7 Retention

Employee and emergency contacts: duration of service plus a records-officer period. Transfers follow the employee. Applicants not hired: 1 year after `decided_at`. Hired applicants merge into the employee record. Audit logs and reference data: indefinite. Periods are pending DPO/records approval, so build the data to support them (timestamps), not automated deletion.

</details>

<details>
<summary><strong>5. Conventions</strong></summary>

**Backend**
- Laravel way: `php artisan make:*` with `--no-interaction`. Typed signatures, constructor promotion, TitleCase enum keys, curly braces always, PHPDoc over inline comments.
- Thin controllers; Form Requests for validation; Policies for record-level authorization; Actions/Services for multi-step operations (transfer, conversion, leave decision) wrapped in `DB::transaction`.
- Named routes and `route()` always. Eloquent API Resources for API output (explicit whitelists).
- Guards: `web` (staff) and `applicant` are separate; route groups carry `auth:<guard>` plus role middleware.
- Errors: validation returns field errors; domain failures return `{ ok:false, error, errors }`-style messages like the prototype; API errors are JSON with 401/403/404/422/429.
- Logging: never log passwords, tokens, or government IDs. Audit logging goes through one `AuditLogger` service, not ad-hoc inserts.
- Factories and seeders for every model; **synthetic data only**.

**Frontend** (see `.ai/rules/frontend.md`)
- Bootstrap utilities and `data-bs-*`; jQuery for DOM; no inline scripts or styles; no CDN; Vite only.
- Pages hook in via `data-page="…"`; behavior in `resources/js/*.js`; reuse `x-` components.
- Validators return `{ field: message }`; keep copy plain and specific.
- Copy the existing module shape (filters, sort, pagination, modals, toasts) for new screens.
- When wiring a page to the backend, replace the `mock-data.js` import with a small API client module that keeps the same function names and return shapes.

**Testing**
- Pest feature tests. Existing tests assert markup; add **authorization tests** (each role on each route: allowed and 403), **validation tests**, and **invariant tests** (transfer row written, conversion rolls back, last HR Head protected, API never returns restricted fields).
- Read the `testing-best-practices` skill before writing tests. Use factories.
- Do not delete tests or test files without approval.

**Avoid:** `Route::view` for data pages once they have data; business rules in Blade or JS only; new state in `mock-data.js`; `Department` wording; raw SQL; storing derived status.

</details>

<details>
<summary><strong>6. Commands</strong></summary>

```bash
# setup / run (Laragon, project at C:\laragon\www\EMS)
composer install
npm install --ignore-scripts
cp .env.example .env && php artisan key:generate
php artisan migrate
composer run dev            # php artisan dev (server + queue + vite)
npm run dev                 # vite only
npm run build               # production assets (needed if "Unable to locate file in Vite manifest")

# tests
php artisan test --compact                              # full suite (ask the user to run it)
php artisan test --compact tests/Feature/CalendarPageTest.php
php artisan test --compact --filter="part of test name"
vendor/bin/pest                                         # same runner

# format PHP (required before finishing any PHP change)
vendor/bin/pint --dirty --format agent

# inspect
php artisan route:list --except-vendor
php artisan config:show database.default
php artisan migrate:status
```

The database is MySQL `ems` on 127.0.0.1 (root, no password locally). There is no JS test runner or JS linter yet.

</details>

<details>
<summary><strong>7. Rules for changes: ask the owner first</strong></summary>

Ask before:
- **Any schema migration** (new tables, column changes, renames, such as department to college), and before dropping or renaming anything.
- **Public API changes**: routes, fields, auth, rate limits, status codes, or adding a consumer.
- **Dependencies**: MFA (TOTP) library, Sanctum, a breached-password checker, and anything in `composer.json`/`package.json`. Note: `php artisan install:api` normally installs Sanctum, so add the `api:` routing key in `bootstrap/app.php` by hand, or get approval.
- **Role or permission changes**, or anything that widens what a role can see.
- **Deleting files, tests, or data**, and removing `mock-data.js` pieces.
- **Retention periods, privacy notice text, DPO-related behavior.**
- **Using real employee data anywhere.** Never.
- Anything marked OPEN in section 0.
- Creating new base folders or documentation files.
- Commits, pushes, PRs: only when asked. Commit trailer: follow the attribution reminder in the session.

Do without asking: add tests, add factories/seeders (synthetic), refactor within a module, fix bugs, update this guide when facts change.

</details>

<details>
<summary><strong>8. Known gotchas and current state</strong></summary>

- **Everything is mock.** 292 Pest tests pass, but they only check rendered markup. None of the business rules in `mock-data.js` has automated coverage; port rules **with** tests.
- **No real auth anywhere.** The HR user is hard-coded as "Maria Santos". The Employee portal is hard-coded to `EMP-1002`. Applicant "login" is a sessionStorage ID. Applicant mock passwords are plaintext (acceptable only as mock).
- **Login page** authenticates applicants only; it must become a staff/applicant-aware flow with separate guards, throttling, MFA for HR, forced password change on first login (temp password expires after 72 hours), and session invalidation on password change.
- **Prototype sample data is fake** (14 departments, 148 employees). Replace with the 7 colleges, the real headcounts, and synthetic people.
- **"Department" wording** is baked into routes (`departments.*`), views, JS, tests, and audit strings. Renaming touches many files; do it as one deliberate change with the migration.
- **IDs** are strings (`EMP-1001`, `d1`, `t1`, `c1`, `lv1`) in the prototype and used in URL constraints (`EMP-[0-9]+`, `t[0-9]+`, `c[0-9]+`). Decide how these map to DB ids; `employee_no` should keep the `EMP-` format.
- **Salary** is currently per employee and per job-title range; the SDD wants rate tiers as reference. Migration of that concept needs the owner's tier table (grade/step/amount).
- **Hire flow** in the prototype copies salary/benefits from the job title and reuses an existing employee by email; keep the reuse behavior but inside the conversion transaction.
- **Benefits** (SSS, PhilHealth, etc.) are reference labels, not payroll. Keep them informational.
- **Documents** are metadata only today.
- **Reports/exports** (employees, recruitment, audit) are client-side and unlogged; the SDD requires HR-only and logged exports.
- **Environment mismatch:** docs say PHP 8.5; the local machine runs PHP 8.4.12 (composer allows `^8.3`).
- **CI/CD** (GitHub Actions + MySQL service, self-hosted runner for deploy) is described in the SDD but not set up in the repo. `tests/Pest.php` has `RefreshDatabase` commented out; enable it when DB tests arrive and verify `phpunit.xml`'s DB settings first.
- **Compliance items** pending DPO/PLP validation: DPIA, privacy notices (applicant consent on the resume form; staff acknowledgement at first login), retention approval, data sharing agreements before any API consumer.
- Programs under each college: not defined yet.

</details>

<details>
<summary><strong>9. Definition of done</strong></summary>

A change is done when:
1. It follows the matching `.ai/rules` file and existing sibling code.
2. Tests exist for the new behavior **and** its failure modes, including authorization (every role) and invariants where relevant; the narrowest affected tests pass (`php artisan test --compact <file>`). Ask the user to run the full suite.
3. `vendor/bin/pint --dirty --format agent` was run on changed PHP.
4. Server-side validation exists (not only JS), and sensitive reads/changes write `audit_logs`.
5. No restricted field can reach the API or an unauthorized role; masked fields stay masked.
6. History tables (`college_transfers`, `rate_tiers`, leave/stage history, audit logs) are append-only.
7. Frontend assets build (`npm run build`) and the page works for each affected role and in both light and dark mode.
8. "College" terminology is used; no payroll or attendance logic was introduced.
9. This guide and the relevant route/file comments are updated if a decision or contract changed; open questions are listed rather than guessed.
10. Nothing was committed or pushed unless the user asked.

</details>
