# Backend Progress

## ▶ How to run the system (step by step)

These steps are for Windows with [Laravel Herd](https://herd.laravel.com), which installs PHP and Composer. Run the commands in PowerShell or the VS Code terminal, from the project folder.

**1. Get the code on the `backend` branch**
```powershell
cd D:\Admission_System
git checkout backend
git pull
```

**2. Install the PHP packages**
```powershell
composer install
```

**3. Create your settings file and keys**
```powershell
copy .env.example .env
php artisan key:generate
php artisan jwt:secret
```

**4. Edit `.env` for local use.** Open `.env` and change these lines:
```
APP_ENV=local
MAIL_MAILER=log
QUEUE_CONNECTION=sync
```
- `MAIL_MAILER=log` writes emails to `storage/logs/laravel.log` instead of sending them.
- `QUEUE_CONNECTION=sync` sends them right away, so no background worker is needed.

**5. Choose a database.**
- **Option A, SQLite (easiest):** set `DB_CONNECTION=sqlite`, delete the other `DB_` lines (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), then create the empty database file:
  ```powershell
  New-Item database\database.sqlite -ItemType File
  ```
- **Option B, MySQL:** create an empty database called `admission_system`, then fill in `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`.

**6. Create the tables and sample data**
```powershell
php artisan migrate --seed
```
This creates the programs, requirements, admission period and the sysadmin account. Because `APP_ENV=local`, it also creates demo staff and 60 sample applicants, which takes about a minute. **Copy the sysadmin password it prints.**

**7. Start the server**
```powershell
php artisan serve
```
Leave this window open. Press `Ctrl+C` to stop the server.

**8. Open the system in your browser**

| Screen | Address | Sign in with |
|---|---|---|
| Applicant portal | http://127.0.0.1:8000/ | Click **Create account**, or use a sample applicant (any email from the Applicants list in the admin panel) with password `password123` |
| Admission office | http://127.0.0.1:8000/admin | `admissions@plpasig.edu.ph` / `password123` |
| Clinic (medical) | http://127.0.0.1:8000/admin | `clinic@plpasig.edu.ph` / `password123` |

**9. (Optional) Sysadmin tasks.** These are command-line only; staff can't do them:
```powershell
php artisan user:create name@plpasig.edu.ph "Full Name" admission   # admission | clinic | sysadmin
php artisan user:active name@plpasig.edu.ph off                     # off = disable, on = enable
php artisan user:2fa name@plpasig.edu.ph totp                       # totp | email | off
```

**10. (Optional) Run the tests, or start over with fresh data**
```powershell
php artisan test
php artisan migrate:fresh --seed
```

**Troubleshooting**
- *"php" or "composer" is not recognized:* install Herd, then open a new terminal.
- *"Database file … does not exist":* you skipped the `New-Item` command in step 5.
- *Sign-in fails right after setup:* check that you ran `php artisan jwt:secret` in step 3.
- *Port 8000 is busy:* use `php artisan serve --port=8080` and open `http://127.0.0.1:8080` instead.

**Production setup** (architecture diagram): Nginx in front ([docs/deploy/nginx.conf](docs/deploy/nginx.conf)), PHP-FPM and RDS MySQL. Set `MAIL_MAILER=failover` with the Gmail and Brevo credentials and `QUEUE_CONNECTION=database`. Run two background processes: `php artisan queue:work` (emails) and `php artisan schedule:work`, or a cron job that runs `schedule:run` every minute. Never commit `.env` to Git.

## ❓ Waiting on answers

1. **How should the 8 applicant document cards map to the official requirements (H1)?** Nothing can be submitted from the portal until this is settled. Details under "On hold" below.
2. **What should happen when a passer doesn't accept or pay by the reservation deadline?** The diagram's "slot deadlines" job can't be automated until this is defined.
3. ~~**MySQL credentials**~~: received; `.env` now uses MySQL (`admission_system` / `admission_app`). The first MySQL run found two problems SQLite couldn't: an index name over MySQL's 64-character limit (fixed), and the audit-log triggers need `log_bin_trust_function_creators = 1` because binary logging is on. **Waiting:** run once as the MySQL root user: `SET PERSIST log_bin_trust_function_creators = 1;` (on Amazon RDS: set it in the DB parameter group).

Tracks the backend build on the `backend` branch. Guide: [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md). Architecture: the system architecture diagram (Nginx communication layer → Laravel on EC2 → RDS MySQL; DMS and Enrollment as external systems). UI audit: [docs/UI_BACKEND_MATRIX.md](docs/UI_BACKEND_MATRIX.md).

The system is one module of the university ERP. Other systems (Document Mgmt, Enrollment) connect through REST + API key, so integrations go behind interfaces and are switched on through `.env`.

## Decisions made with the team (2026-10-17)

These override DEVELOPMENT_PLAN.md where the two disagree.

| # | Topic | Decision | Overrides |
|---|---|---|---|
| T1 | Wiring the UI | Change only the JS data layer (localStorage and `BACKEND:` stubs → AJAX). No markup, CSS, text or visible behaviour changes | Plan §0.4 "never modify client-side JS" |
| T2 | Medical requirements (FR-13) | Build now, with a separate **Clinic** role, following the diagram and the existing medical screens | Plan D5 (deferred) |
| T3 | Notifications | In-app **and** email: Gmail SMTP with Brevo as failover, sent from the queue | Plan D4 (in-app only) |
| T4 | 2FA | Built in the backend: authenticator app (TOTP) for staff, email code for applicants. UI pending, since there is no code screen yet | Not in plan |
| T5 | Document storage | `DocumentStore` interface: private local disk now, DMS REST driver (API key) switched on via `.env` when the DMS exists. The database stores the storage reference (DMS document ID) | Plan Phase 4 (local only) |
| T6 | Official document list | The admin panel's 7 conditional requirements | — |
| T7 | Applicant form gaps | Store the form as it is; derive residency from city; district/barangay empty until the UI has fields | — |
| T8 | Upload limit | 5 MB (diagram), PDF/JPG/PNG | UI text says 10 MB |
| T9 | Enrollment integration | Later. Keep the design ready for it | — |
| T10 | Roles | `applicant`, `admission`, `clinic`, `sysadmin` (diagram's four user groups) | Plan's `applicant/staff/admin` |
| T11 | Auth | JWT (`php-open-source-saver/jwt-auth`), sent as a bearer token by the AJAX calls | — |

### On hold: to discuss with the team

| # | Topic | Detail | Blocks |
|---|---|---|---|
| H1 | Applicant document cards vs official list | The portal shows 8 cards; the official list (T6) has 7 conditional requirements. 5 cards map cleanly (Valid ID→`applicantId`, Parent/Guardian ID→`parentId`, CTC Grade 11→`form138`, PSA→`psa`, Photo→`photo`). 3 cards have no official requirement (Grade 12 Form 138, Good Moral, GCash/Dragonpay payment). 2 requirements have no card (Guardianship affidavit, Form 137). The portal only enables "Submit" when all 8 cards are filled. **Nothing is built for this mapping until it's discussed.** | Wiring the applicant's document cards and the submit button. The backend upload/review API is keyed by official requirement and is built. |

Still open (defaults in use): D6 file types, D8 status set (using FR-05 set: draft, submitted, resubmit, for_exam, passed, failed), D9 freshmen only, D10 soft deletes.

## Environment

- Laravel 13, PHP 8.4 (Herd). MySQL 8 is installed locally but needs credentials; development currently uses SQLite (`database/database.sqlite`) and tests use in-memory SQLite. Migrations are written for MySQL too (including the append-only triggers) but **have not been run on MySQL yet**.
- Packages: `php-open-source-saver/jwt-auth`, `spatie/laravel-permission`, `pragmarx/google2fa`, `laravel/pulse`, `laravel/boost` (dev).

## Phases

Legend: ✅ done · 🔄 in progress · ⬜ to do · ⏸ deferred

| Phase | Scope | Status |
|---|---|---|
| 0 | UI audit → [UI_BACKEND_MATRIX](docs/UI_BACKEND_MATRIX.md) | ✅ |
| 1 | Foundation & data layer: migrations, models, seeders, API response format | ✅ |
| 2 | Auth & access control: JWT, roles, 2FA, password reset, throttling | ✅ |
| 3 | Application: profile, draft/autosave, program choices, submit, status service, history | ✅ Wired (submit button on hold: H1) |
| 4 | Documents: upload (DocumentStore), review, resubmission | ✅ Staff side wired · applicant cards on hold (H1) |
| 5 | Programs, slots, requirements, admission period maintenance | ✅ Wired |
| 6 | Exams: sessions, approval/permit, roster, attendance, reschedule | ✅ Wired |
| 7 | Results & acceptance: record, release, accept offer (no score to applicants) | ✅ Wired |
| 7b | Medical requirements (Clinic) | ✅ Wired |
| 8 | Records, reports, notifications (in-app + email), audit log | ✅ (reports computed in the panel from `/api/admin/state`) |
| 9 | Enrollment integration, automated document checks | ⏸ |
| 10 | Hardening, performance (server-side paging), docs, UAT | 🔄 Security headers, Nginx sample, CI done; rest below |

## Work log

### 2026-10-17
- Converted the static site into a Laravel project (pages → Blade views, assets → `public/assets`). UI unchanged.
- Created the `backend` branch.
- Phase 0: audited both UIs, wrote the matrix and the discrepancy list, and agreed decisions T1–T11 with the team.
- Installed the packages above and removed Sanctum (JWT is used instead).

- Phase 1: schema (periods, programs, generic requirements incl. medical, applicants, applications, choices, versioned documents with storage driver/ref, exam sessions/registrations/results, status history, change history, audit log, notifications). History and audit tables are append-only, enforced by the model **and** by DB triggers (MySQL + SQLite). Seeders: roles, programs, requirements, active period, sysadmin, local demo staff + exam sessions. Mail config: Gmail → Brevo failover. Tests: 4 passing.

- Phase 2: `/api/auth/*` (register, login with `portal` = applicant|office, 2FA verify, me, refresh, logout, forgot/reset password). Each portal only admits its own roles; sysadmin can't use either. Disabled accounts lose access immediately. 2FA: TOTP for staff, email code for applicants (off by default via `APPLICANT_EMAIL_2FA`). Rate limits on login/register. Sysadmin artisan commands: `user:create`, `user:active`, `user:2fa`. Staff logins/logouts audited. Tests: 17 passing.

- Phase 3: `GET /api/applicant/portal` returns the portal state in the exact shape `applicant.js` keeps in localStorage (phase, step, completed, form data, exam, released result only, medical, programs, key dates). `PUT /api/applicant/application` autosaves: half-typed values are skipped and reported, sections marked complete are validated strictly (rules mirror the form's `required`/`pattern`). Program choices 1–3 by name, open programs only, no duplicates. `POST /api/applicant/submit` checks form + required documents and moves Draft/Resubmit → Submitted through `ApplicationStatusService` (the only place status changes; writes history + fires `ApplicationStatusChanged`). Resubmission reopens the portal as "draft" so existing screens can be reused. Profile edits after the first submission go to `applicant_change_history` (FR-10). `ProgramSuggestionService` placeholder (D2). Tests: 26 passing.

- Phase 4 + medical: `DocumentStorage` writes new files to the configured store (`local` private disk or `dms`) and reads each file from the store it was saved in, so switching to the DMS doesn't strand old files. DMS driver: `X-API-Key`, `Idempotency-Key`, retries, `confidential` flag on medical files (assumed REST contract, adjust when the DMS API is published). Uploads: content-checked PDF/JPG/PNG (photo image-only), 5 MB, random names, versioned (old versions kept). Applicant: upload/replace/remove in draft; during resubmission only the files sent back. Staff: verify / send back (remark required) / verify all pending; the application moves to "Requires resubmission" and back automatically. Medical: opens after a released pass; clinic-only review and file access (admission staff can't open medical files, the clinic can't open admission documents). `GET /api/admin/state` returns the panel's full `ADM.state` shape (score for admission personnel only). Times shown in Asia/Manila. Tests: 34 passing.

- Phases 5–7: setup endpoints (slots 1–2,000, never below admitted count; add program; requirement on/off; deadline), exam sessions (no duplicates), approve-for-exam (all required docs verified, seat check under a row lock, permit `EX-27-xxxxx`), reschedule, venue checklist + attendance ("present" needs permit and form checked), record/correct result before release, single/bulk release, accept offer. Clinic is limited to medical routes. Test proves no applicant response contains a score or percentile, and the clinic's state has no score. The applicant acceptance letter is rendered by the existing modal from portal data, so no PDF was built (no UI asks for one). Tests: 45 passing.

- Phase 8: `AdmissionNotice` (database + mail, queued) on: application received, resubmission received, document/medical file sent back (with the remark), exam scheduled/rescheduled (date, time, venue, permit), result released (no decision, no score: "sign in to see it"), medically cleared. Recording a result notifies nobody. `GET /api/notifications`, mark read / read all (UI pending). Scheduler prunes failed jobs. Audit log is fed by every service (staff actions, applicant submissions, sysadmin commands). Tests: 48 passing.

- Demo data: `DemoApplicantsSeeder` (local only) walks 60 applicants through the real services at back-dated times, so statuses, histories and the audit log are consistent. Demo logins: `admissions@plpasig.edu.ph`, `r.dizon@plpasig.edu.ph`, `clinic@plpasig.edu.ph` / `password123`.
- **Admission office panel wired** (data layer only): `core.js` gained `AO.api` (JWT bearer, JSON or file upload), `AO.apiError`, `AO.busy`. `admin-store.js` loads `ADM.state` from `GET /api/admin/state` (demo generator and localStorage removed). `admin-actions.js` keeps each action's instant local update, then sends it to the API on one ordered queue; the server's copy replaces the local one, and a refusal shows the server's message and reloads. `admin.js`: real sign-in (`portal: office`), session restore per tab, sign-out, expired-token handling. Document preview fetches the real file (the one visible change, as the `BACKEND:` comment specified). Verified in headless Edge: officer and clinic flows, persistence across reloads, role refusals, no page errors.
- Fixed: `AuditLogger` now resolves the actor through `auth()` (the request had no user in console runs).

- **Applicant portal wired** (data layer only): sign-in (`portal: applicant`), create account (server errors shown on the matching fields), forgot password, sign-out, session restore. The portal state comes from `GET /api/applicant/portal`; every `save()` sends the form and section progress to the server (debounced). Program options, key dates, the exam schedule, the application number, the offer letter dates, the released result, "Accept offer" and the medical uploads/submit all use the server. A value the server refuses although it looks valid (e.g. an LRN already in use) is shown on its field. **Document cards and "Submit" stay browser-only (H1)**, kept per application in localStorage. Verified in headless Edge: register → fill 3 sections → reload (data back from the server) → sign out → wrong password → passer: accept offer → 3 medical uploads → submit; no page errors.

- Phase 10 (part): `SecurityHeaders` middleware (nosniff, frame, referrer, permissions policy); sample Nginx config for the communication layer in [docs/deploy/nginx.conf](docs/deploy/nginx.conf) (TLS 443, static files, `/api` to PHP-FPM, 6 MB body cap, rate limits, IP allowlist for Pulse, outbound proxy for DMS/Enrollment); GitHub Actions workflow running the test suite; unit tests for the status transition rules. Tests: 51 passing.

## Remaining work

| # | Item | Notes |
|---|---|---|
| R1 | Wire the applicant document cards and "Submit" | Waiting on H1 |
| R2 | Run migrations and tests against MySQL | Needs local MySQL credentials; then on RDS |
| R3 | Server-side paging for the admission office panel | The panel loads every applicant at once (fine for the demo; not for ~20,000). Needs view changes, so the UI team should be involved |
| R4 | DMS driver against the real API | The request shape is assumed; adjust `DmsDocumentStore` when the DMS publishes its API |
| R5 | Enrollment integration | Deferred (T9): queued push of the admitted list with `X-API-Key` + `Idempotency-Key`, callback for student numbers |
| R6 | DB least privilege | App DB user gets INSERT/SELECT only on `audit_logs` and the history tables (triggers already block edits) |
| R7 | Backups | `artisan` backup command or RDS automated backups (sysadmin, not staff) |
| R8 | Pulse dashboard access in production | Pulse is installed and recording; its dashboard is allowed only locally by default. Decide who sees it (sysadmin over VPN via the Nginx allowlist) |
| R9 | Load test before the application deadline | Get a real peak figure from the admission office |
| R10 | UAT with the admission office on the existing screens | |

## Open questions for the team

1. **H1**: how the portal's 8 document cards map to the official requirements (see above).
2. **UI gaps** (backend is ready, the UI can't show it yet): 2FA code screen, "new password" screen for the reset link, notification list, a "Did not pass" result screen (failed applicants currently stay on the exam step), hiding admin screens by role (the clinic sees every screen; the server refuses what isn't theirs), district/barangay fields in the applicant form, the unreachable profile view. Full list: [docs/UI_BACKEND_MATRIX.md](docs/UI_BACKEND_MATRIX.md).
3. The applicant result screen has the hard-coded text "Passed · 89th percentile". The backend never sends a score or percentile (NFR-02), but the text itself should be removed by the UI team.
4. "Slot deadlines" in the scheduler (architecture diagram): what should happen when a passer doesn't accept or pay by the reservation deadline? Nothing is automated until this is defined.
5. Who owns sysadmin duties (D7), and the clinic's real medical requirement list (the seeded one is a placeholder)?
6. What should "Reset demo data" in the panel do on a real server? It currently just reloads.
