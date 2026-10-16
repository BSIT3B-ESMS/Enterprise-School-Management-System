# Backend Progress

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

- Laravel 13, PHP 8.4 (Herd). MySQL 8 is installed locally but needs credentials; development currently uses SQLite (`database/database.sqlite`) and tests use in-memory SQLite. Migrations are written to run on MySQL too.
- Packages: `php-open-source-saver/jwt-auth`, `spatie/laravel-permission`, `pragmarx/google2fa`, `laravel/pulse`, `laravel/boost` (dev).

## Phases

Legend: ✅ done · 🔄 in progress · ⬜ to do · ⏸ deferred

| Phase | Scope | Status |
|---|---|---|
| 0 | UI audit → [UI_BACKEND_MATRIX](docs/UI_BACKEND_MATRIX.md) | ✅ |
| 1 | Foundation & data layer: migrations, models, seeders, API response format | ✅ |
| 2 | Auth & access control: JWT, roles, 2FA, password reset, throttling | ✅ |
| 3 | Application: profile, draft/autosave, program choices, submit, status service, history | ✅ (UI wiring pending) |
| 4 | Documents: upload (DocumentStore), review, resubmission | ✅ (applicant cards on hold: H1) |
| 5 | Programs, slots, requirements, admission period maintenance | ⬜ |
| 6 | Exams: sessions, approval/permit, roster, attendance, reschedule | ⬜ |
| 7 | Results & acceptance: record, release, accept offer (no score to applicants) | ⬜ |
| 7b | Medical requirements (Clinic) | ✅ (built with Phase 4) |
| 8 | Records, reports, notifications (in-app + email), audit log | ⬜ |
| 9 | Enrollment integration, automated document checks | ⏸ |
| 10 | Hardening, performance (server-side paging), docs, UAT | ⬜ |

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

## To do next

- Phase 5: programs, slots, requirements, deadline (staff maintenance).
- Get MySQL credentials and run the migrations against MySQL.
