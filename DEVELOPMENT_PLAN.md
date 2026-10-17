# PLP Online Admission System (OAS) — Backend Development Plan

**Source:** *Initial Draft* (Solution Design Document, v0.1)
**Stack (from paper §7.2):** Laravel (PHP) backend · MySQL · JWT + RBAC · existing HTML/CSS/JS/Bootstrap/jQuery frontend
**Scope of this plan:** Backend only. **No UI changes.**

---

## 0. Instructions for the Implementing Agent

This file is meant to be read by Claude inside the project. Before writing any code:

1. **Inspect the project first.** Find the existing UI (views, HTML/JS, routes, forms) and any existing backend. Read it; don't assume.
2. **Do Phase 0 (UI audit) and produce the matrix before building.**
3. **Only wire backend to UI that exists.** If a screen exists for a feature (including any admin screen for accounts, backups or security settings), build the backend for it. If none exists, build API-only or skip, as stated per phase.
4. **Never modify UI files** (markup, CSS, client-side JS, layout, text). If something looks wrong or incomplete, report it; don't fix it.
5. **FR-13 (medical requirements) is not built now**, but follow the "FR-13 readiness" rules in §3.
6. **The entrance exam is pen-and-paper, taken at a venue.** The system never delivers or scores an exam.

---

## 1. Ground Rules

1. **Backend-only.** The existing UI is incomplete. We only connect backend logic to UI parts that **already exist**. We do not add, remove, restyle, or re-layout any page, form, button, or component.
2. **UI is the contract.** Where a UI element exists, the backend adapts to it (field names, form structure, expected JSON/Blade variables, URLs). We do not ask the UI to adapt to us.
3. **No UI = no wiring.** Features with no existing UI are built as API-only and left unwired, or deferred (FR-13 is deferred outright). They are listed in §9 as "backend ready, UI pending".
4. **Do not invent unresolved scope.** The paper has open decisions (see §3). We build around them behind flags/stubs rather than guessing.
5. **Security requirements are non-negotiable:** numerical scores are never exposed to applicants (NFR-02), staff cannot manage accounts/backups/security settings or edit audit logs (NFR-01, NFR-03).

---

## 2. Phase 0 — Existing UI Audit (do this first)

The UI codebase was not available when this plan was written, so Phase 0 produces the inventory that every later phase depends on. Run it against the actual project.

| Step | Output |
|---|---|
| 0.1 Identify how the UI talks to a server: Blade views, static HTML + jQuery AJAX, or form posts | Integration style decision (§6) |
| 0.2 List every existing page/screen per role (Applicant, Admission Personnel) | Page inventory |
| 0.3 For each page, record: forms (field `name`s), buttons/actions, AJAX calls, tables/lists, expected data shape, any hard-coded/mock data | UI contract sheet |
| 0.4 Mark each element: **Complete / Partial / Placeholder / Missing** | Wiring matrix |
| 0.5 Map each existing UI element to an FR/NFR from the paper | Traceability to FR-01…FR-13 |

**Deliverable:** `UI_BACKEND_MATRIX` (a table: *Page → UI element → FR → endpoint → status*). Phases 2–7 only build endpoints that have a row in this matrix with status Complete/Partial.

Suggested template:

| Page | UI element | Needs | FR/NFR | Endpoint | Status |
|---|---|---|---|---|---|
| e.g. Register | Registration form | Save applicant | FR-01 | `POST /api/auth/register` | To do |

---

## 3. Open Decisions That Affect the Backend

From the paper's notes. Each gets a **default assumption** so work isn't blocked. Rows marked **✅ Confirmed** were confirmed by the team on 2026-10-17; the rest still need confirmation with the team/client.

| # | Open issue (paper ref.) | Default assumption for build | Impact |
|---|---|---|---|
| D1 ✅ Confirmed | Is the exam taken **online**, or only scheduling/permit/attendance/results? (§3.4, FR-06/07) | **Exam is NOT taken online; it is a pen-and-paper exam at a venue.** Build **FR-06 only** (schedule, permit, roster, attendance, internal result recording; staff enter scores from the marked papers). **FR-07 is out of scope**: no question bank, timer, or auto-scoring. Reopen only if the team changes this decision | — |
| D2 ✅ Confirmed | Suggested-programs rules (FR-02) | The paper requires a "suggested programs" list but gives no rules for producing it. Store the 3 program choices normally, and build the suggestions as a **simple placeholder**: one class (`ProgramSuggestionService`) that currently returns programs that are open and still have slots. The endpoint calls only that class, so when real rules are defined (e.g., based on strand), only that class changes, not the endpoint or UI | Small |
| D3 ✅ Confirmed | Automatic document verification (FR-04) | **Manual staff review only** | Small |
| D4 ✅ Confirmed | Notification channel/triggers (FR-12) | **In-app (database) notifications only**, triggered on status changes. No email/SMS | Medium |
| D5 ✅ Confirmed | Medical requirements workflow (FR-13) | **Not built now.** No UI and no defined workflow. Keep it in mind via the "FR-13 readiness" rules below; add no tables, routes, or statuses for it | Deferred |
| D6 | Upload vs. physical documents; allowed file types/size (§8.1) | Config values: PDF/JPG/PNG, 5 MB (configurable). Per-requirement flag `is_uploadable` | Small |
| D7 ✅ Confirmed | Who does account admin, backups, security settings (§10.2) | Staff **cannot** do these (flow confirmed). Whether a UI exists for them is undecided, so **check the project**: if an admin screen exists, build its backend under the `admin` role; if not, provide a seeded `admin` user and artisan commands only, and add no UI. Owner of these duties still to be named | Medium |
| D8 | Applicant status set: paper lists two different sets (§4 Proposed Solution vs FR-05) | Use **FR-05 set**: Draft, Submitted, Requires Resubmission, For Exam, Passed, Did Not Pass | Small |
| D9 | Freshmen only? Data migration of old records? (§3.4, §12.2) | Freshmen only; **no migration**, new applications only | Small |
| D10 | Retention, privacy rules, hosting (§8.3, §11) | Out of build scope; keep deletion soft (`deleted_at`), keep config env-based | Small |

**FR-13 readiness (keep in mind, do not build).** The paper says medical requirements are the *last* admission step, after the result. To avoid a rewrite later:
- Keep the status list in one place (enum / `ApplicationStatusService`) so a later status or sub-step can be added without touching callers.
- Keep `requirements` and `application_documents` generic so medical items could later be added as requirements if the team chooses that route.
- Don't treat "Passed" as the permanent final state in code, reports, or letters (e.g., avoid hard-coded "completed" logic).
- Add nothing medical-specific now.

---

## 4. Architecture

```
Existing UI (unchanged)
   │  HTTP (AJAX / form posts)
   ▼
Laravel app
 ├─ routes/api.php (or web.php, per Phase 0)
 ├─ Middleware: auth:api (JWT) → role:applicant|staff|admin → throttle
 ├─ Controllers (thin)  →  Form Requests (validation)
 ├─ Services (business rules: status transitions, scoring, slots)
 ├─ Policies (own-record access)
 ├─ Models / Eloquent  →  MySQL
 ├─ Events + Listeners (audit log, notifications)
 └─ Storage (private disk for uploads)
```

**Key principles**
- Thin controllers, logic in services — keeps wiring to existing UI simple and testable.
- Status changes go through **one** `ApplicationStatusService` that enforces allowed transitions, writes history, and fires notifications.
- Response shapes are driven by the UI contract sheet (Phase 0).

**Packages (suggested):** `tymon/jwt-auth` (JWT), `spatie/laravel-permission` (RBAC), `spatie/laravel-activitylog` or a custom audit table (NFR-03), `barryvdh/laravel-dompdf` (permit, application form, acceptance letter), `maatwebsite/excel` (report export, only if UI has an export button).

---

## 5. Database Design

Paper §8.1 says the ERD is not final, so this is the **proposed schema**; finalize the ERD at the end of Phase 1.

| Table | Key fields | Notes / FR |
|---|---|---|
| `users` | id, email (unique), password, role, is_active | One account per email (FR-01) |
| `roles` / `permissions` | via spatie | `applicant`, `staff`, `admin` |
| `applicants` | user_id, lrn (unique), surname, given_name, middle_name, sex, birthday, contact_no, residency_status, district, barangay, address, school_type (public/private), last_school, strand | FR-01, §8.1 registration fields |
| `applications` | applicant_id, academic_year/admission_period_id, status, submitted_at, locked_at | FR-05 |
| `application_program_choices` | application_id, program_id, rank (1–3) | FR-02, unique (application, rank) |
| `programs` | name, code, slots_total, slots_filled, is_open | FR-09 |
| `admission_periods` | label, start/end dates, is_active | Supports FR-09 "before each period" |
| `requirements` | name, description, is_uploadable, is_required, applies_to rule | FR-09; seeded from the freshman list |
| `application_documents` | application_id, requirement_id, file_path, original_name, mime, size, status (Pending/Verified/Rejected/Requires Resubmission), staff_remark, reviewed_by, reviewed_at, version | FR-03/04 |
| `exam_schedules` | admission_period_id, batch (A morning/B afternoon), date, venue, capacity | FR-06 |
| `exam_registrations` | application_id, schedule_id, permit_no, attended (bool), attended_at | Roster + attendance |
| `exam_results` | application_id, score (**internal**), result (Passed/Did Not Pass), is_released, released_by, released_at | FR-08, NFR-02 |
| `acceptance_letters` | application_id, file_path/template data, generated_at | FR-08 |
| `application_status_history` | application_id, from_status, to_status, changed_by, remark, created_at | FR-05, FR-10 |
| `applicant_change_history` | applicant_id, field, old_value, new_value, changed_by, created_at | FR-10 |
| `audit_logs` | user_id, action, entity, entity_id, ip, meta JSON, created_at | NFR-03; **append-only** |
| `notifications` | user_id, type, data JSON, read_at | FR-12 |
| *(deferred)* `medical_submissions` | — | D5. Exam question/attempt tables are **not needed** (D1) |

**Integrity rules**
- `audit_logs` and `*_history`: no UPDATE/DELETE exposed anywhere in app code; DB user for the app has INSERT/SELECT only on `audit_logs` (hardening step).
- `exam_results.score` is excluded from every applicant-facing resource/query.
- Slot counts updated transactionally.

---

## 6. UI Integration Approach

Decide in Phase 0 which applies; the backend work is the same, only the response layer differs.

| Existing UI type | How backend plugs in |
|---|---|
| Static HTML + jQuery AJAX | JSON API; match URLs, methods, field names, and JSON keys the existing JS already calls. Add CORS config if served separately. JWT stored/sent the way the existing JS expects (header or cookie) |
| Laravel Blade views | Controllers return the existing views with the variables they already reference; form posts use existing `name` attributes and CSRF tokens. JWT guard used only where an API is needed |
| UI uses mock/hard-coded data | Replace the mock source with real queries via a JSON endpoint/controller variable of the **same shape**. Do not edit the markup |

If an existing element's expectations conflict with the paper (e.g., the UI shows a numeric score to applicants), **do not change the UI**; implement the backend per the paper's rule (return no score) and log it in the matrix as a discrepancy for the team.

---

## 7. Development Phases

Each phase ends with: feature tests passing, wiring verified against the UI matrix, no UI files modified (`git diff` on frontend paths must be empty/unchanged).

### Phase 1 — Foundation & Data Layer
- Laravel project setup (or use existing), `.env`, MySQL connection, config for upload limits/disk.
- Migrations + models + factories + seeders for the schema in §5.
- Seeders: roles, an `admin` user, sample programs, the freshman requirements list, one admission period.
- Base API response format, global exception handler (consistent JSON errors), request validation conventions.
- Finalize ERD and update paper §8.1.
- **Exit:** `migrate:fresh --seed` works; ERD documented.

### Phase 2 — Authentication & Access Control (NFR-01) · *FR-01 partial*
- Register (email unique, password rules), login, logout, refresh, `me` via JWT.
- Role middleware (`applicant`, `staff`, `admin`); policies so applicants only read/write **their own** records.
- Password reset via token (log/mail driver; email delivery off by default per D4).
- Explicit denial: staff routes cannot touch user/role management, backups, security settings, or audit-log mutations (those routes simply don't exist for staff).
- Throttle login/register.
- **Exit:** tests for each role against protected routes; applicants cannot access other applicants' data.

### Phase 3 — Applicant Registration & Application (FR-01, FR-02, FR-05)
- Applicant profile create/update (all §8.1 fields; LRN uniqueness and format validation; residency, Pasig district/barangay, school type, strand).
- Application lifecycle: create Draft → save progress → submit (validates completeness, locks editing, sets `Submitted`).
- Program choices 1–3 (no duplicates, program must be open).
- Suggested programs service (stub per D2).
- Status service with transition rules + history (FR-05):
  `Draft → Submitted → (Requires Resubmission ↔ Submitted) → For Exam → Passed | Did Not Pass`
- Applicant change-history recording on profile edits after submission (FR-10).
- **Exit:** applicant can register, fill, and submit through existing UI forms; status timeline endpoint returns history.

### Phase 4 — Documents: Upload, Review, Resubmission (FR-03, FR-04)
- Upload to **private** storage; validate type/size/MIME; randomized filenames; never served by public URL — use authorized download route.
- Per-requirement upload; applicant sees status + staff remark; re-upload only when `Requires Resubmission` (keeps prior version).
- Staff review queue (filter by status/program/period), verify / reject / request resubmission; **remark required** on reject/resubmission.
- Auto-update application status: any doc needs resubmission → `Requires Resubmission`; all required docs verified → eligible for `For Exam`.
- Notification event on each review decision.
- **Exit:** full upload → review → resubmit loop works with existing UI screens.

### Phase 5 — Programs, Slots & Requirements Maintenance (FR-09)
- Staff CRUD for programs, slot counts, requirements, and admission periods.
- Slot enforcement; block changes that would drop slots below filled count.
- Changes recorded in audit log.
- **Exit:** staff updates data; applicant-side lists reflect it.

### Phase 6 — Examination Administration (FR-06 only; FR-07 out of scope per D1)
- Staff create exam schedules (Batch A/B, date, venue, capacity).
- Assign/self-schedule applicants per capacity rules (confirm which with team).
- Exam is pen-and-paper: nothing is taken or auto-scored in the system.
- Permit and application-form PDF generation (applicant-owned, only when eligible).
- Roster per schedule and attendance list; staff mark attendance.
- Staff move eligible applicants to `For Exam`.
- **Exit:** schedule → permit → roster → attendance works end-to-end.

### Phase 7 — Results & Acceptance (FR-08, NFR-02)
- Staff record internal numerical score (from the marked paper) and result (Passed / Did Not Pass), edit before release.
- **Controlled release:** single and bulk release; only released results visible to applicants.
- Applicant results endpoint returns **only** Passed/Did Not Pass — score field absent at the resource layer (not just hidden); test asserts this.
- Acceptance letter PDF generated for Passed applicants only.
- Status updates + notifications on release.
- **Exit:** applicant never receives a score in any response; verified by automated test.

### Phase 8 — Records, Reports, Notifications, Audit (FR-10, FR-11, FR-12, NFR-03)
- Applicant record view for staff (full record + status/change history).
- Review-queue counts by status.
- Reports: applicants per program, status breakdown, document completion, exam attendance, pass rate, daily submissions, passed/did-not-pass list per program (JSON; export only if the UI already has an export control).
- Notifications: in-app notification list, mark-as-read; triggers on submit, doc decision, exam scheduled, result released.
- Audit logging via event listeners on all sensitive actions (login, status change, doc review, result record/release, setting changes). Append-only; no staff endpoint to edit/delete.
- **Exit:** reports match seeded test data; audit entries created and immutable.

### Phase 9 — Deferred (only if the team reopens the decision, and only with existing UI)
- **Medical requirements** (FR-13): once the workflow is defined (what is required, upload vs. physical, who checks, how completion is shown).
- **Email/SMS delivery** (FR-12): provider integration, if in-app notifications prove insufficient.
- **Automated document checks** (D3): only after the pending consultation.

*Removed from the plan:* the online exam engine (FR-07) — the exam is not taken online (D1).

### Phase 10 — Hardening, Testing, Handover
- Security: input validation review, mass-assignment protection, file-upload checks, rate limiting, secure headers, HTTPS config, secrets in `.env`, DB least-privilege for audit tables.
- Performance: indexes (email, LRN, status, program, period), pagination everywhere, eager loading; load test with realistic peaks (paper cites up to ~20,000 applicants — **not** concurrent users; get a real peak figure).
- Testing: see §8.
- Docs: API reference (matching the UI matrix), setup guide, seeding guide, `.env.example`, artisan commands for admin tasks (user/role management, backup) since staff can't do them.
- UAT with the Admission Office on the existing UI screens.

---

## 8. Testing Strategy (answers paper §12.3 for the backend)

| Layer | Approach | Covers |
|---|---|---|
| Unit | Status transition rules, slot math, score→result, file validation | FR-05, FR-08, FR-09 |
| Feature (HTTP) | Every endpoint × role (applicant, staff, admin, guest) | NFR-01, access control |
| Security | Applicant A cannot read B; staff cannot reach admin routes; score never in applicant payloads; audit table immutable | NFR-01/02/03 |
| Integration | Register → submit → upload → review → exam → result → letter | FR-01…FR-08 |
| UI wiring | For each matrix row: the existing UI action succeeds against the backend (manual checklist, or Playwright against the unchanged UI) | Phase 0 matrix |
| Regression guard | CI check: no changes to frontend directories | Ground rule 1 |

Traceability: every test names its FR/NFR so Appendix B of the paper (currently "Not yet prepared") can be filled.

---

## 9. Requirement Coverage Summary

| Req. | Backend phase | Status in this plan |
|---|---|---|
| FR-01 Registration | 2, 3 | Planned |
| FR-02 Program choices & suggestions | 3 | Choices planned; suggestions stubbed (confirmed, D2) |
| FR-03 Document submission | 4 | Planned |
| FR-04 Application review | 4 | Manual only (confirmed, D3) |
| FR-05 Status timeline | 3 | Planned |
| FR-06 Exam administration | 6 | Planned |
| FR-07 Online exam | — | **Out of scope (confirmed, D1)** |
| FR-08 Decisions & acceptance letter | 7 | Planned |
| FR-09 Programs/slots/requirements | 5 | Planned |
| FR-10 Applicant records & history | 3, 8 | Planned |
| FR-11 Monitoring & reports | 8 | Planned |
| FR-12 Notifications | 8 | In-app only (confirmed, D4) |
| FR-13 Medical requirements | 9 | **Deferred (confirmed, D5)** |
| NFR-01 Identity & access | 2 | Planned |
| NFR-02 Score confidentiality | 7 | Planned |
| NFR-03 Auditability | 8 | Planned |

"Backend ready, UI pending": any module above with no matching existing UI screen is built and tested as API only and stays unwired until the UI exists.

---

## 10. Suggested Order & Dependencies

```
Phase 0 (UI audit)
   └─ Phase 1 (DB)
        └─ Phase 2 (Auth/RBAC)
             ├─ Phase 3 (Application) ─ Phase 4 (Documents) ─ Phase 6 (Exam) ─ Phase 7 (Results)
             └─ Phase 5 (Programs/Requirements)  [needed by 3 & 4; do early alongside 3]
Phase 8 (Records/Reports/Notif/Audit) — audit hooks start in Phase 2, finish here
Phase 9 (deferred: FR-13, email/SMS, auto doc checks)
Phase 10 (Hardening/UAT)
```

Practical note: Phase 5's seed data (programs, requirements) is needed by Phases 3–4, so seed it in Phase 1 and build the staff CRUD afterwards.

**Rough relative effort** (no calendar dates; the paper has no agreed schedule): P0 S · P1 M · P2 M · P3 L · P4 L · P5 S · P6 M · P7 M · P8 L · P10 M. Fix dates once start/deadline and team size are confirmed.

---

## 11. Risks

| Risk | Mitigation |
|---|---|
| Existing UI expects different field names/shapes than the paper implies | Phase 0 contract sheet; adapt backend; log discrepancies |
| Exam scope (D1) is reopened later | Exam is venue-based by decision; results table is independent of how scores are produced, so an online engine could be added later without a rewrite |
| Incomplete UI hides untested backend paths | Test API directly (feature tests), not only through UI |
| Sensitive data (IDs, grades, scores) leakage | Resource classes with explicit allow-lists; private storage; authorization tests |
| Admin duties unassigned (D7) | Artisan commands + seeded admin; confirm owner with the client |
| Peak load at application deadline | Indexing, pagination, queue for PDFs/notifications, load test before launch |

---

## 12. Definition of Done (per feature)

- Endpoint(s) exist for an **existing** UI element listed in the matrix and work with the UI **unchanged**.
- Validation, authorization, and audit logging in place.
- Feature + security tests pass and are tagged with FR/NFR IDs.
- No frontend files modified.
- Matrix row updated to **Wired**.
