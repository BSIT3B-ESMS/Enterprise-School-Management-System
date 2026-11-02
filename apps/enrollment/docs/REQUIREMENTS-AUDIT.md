# DOCX implementation review — October 30, 2026

Project reviewed: C:\Users\Mitchell\OneDrive\Desktop\enrollment management system
Source: ENROLLMENT-MANAGEMENT-SYSTEM.docx supplied by Mitchell.

This is a local implementation and verification report, not institutional acceptance or a claim of exhaustive correctness. Requirements in the document were treated as the specification; example payloads, campus layouts and workflows were not treated as verified production data.

## Coverage and evidence

| DOCX sections | Local implementation | Verification / remaining boundary |
|---|---|---|
| 1–3 Overview, objectives, roles | Student, Registrar and program-scoped Department Head portals; special classifications; no public registration | Authentication, ownership, role and program isolation tests. Department Heads cannot prepare regular proposals. |
| 4 Admission | Authenticated push API and approved JSON file exchange, external-ID matching, unique email, invitations or first-login temporary passwords, sanitized import logs | Synthetic import and API tests. Actual Admission contract and connectivity are not verified. |
| 5 EMS | Employee-ID import/update, active status, department, employment type, optional teaching authorizations, synchronization logs | Synthetic import tests. Actual EMS contract and credentials are not supplied. |
| 6 Curriculum | Versions, academic-year field, required/elective requirements, semester/total units, preserved assignments and historical versions | Reassignment tests preserve old requirements and saved CORs. Open proposals block reassignment. Curriculum changes stay within the same program. |
| 7 Subjects, prerequisites, records | Subject metadata, alternatives, minimum grades, cycle rejection, completion history, retake labels, subject equivalency evidence | Eligibility tests and credit transaction tests. Grade direction and actual credit policy still require school configuration/approval. |
| 8 Offerings | Separate offerings with period/section/capacity/status and optional offering-specific permitted days | Backend validates both subject and offering days. Blank offering days inherit subject restrictions. |
| 9 Sections | Registrar-controlled creation, configurable default 40, distinct student counts, available seats, full-section rejection | MariaDB two-process last-seat test plus distinct section-capacity test. Pending proposals do not reserve seats. |
| 10 Rooms | Buildings/floors, room code/type/capacity/equipment, dedicated-subject option and recurring closures | Schedule/capacity/closure validation tests. Actual campus inventory must be loaded; room code serves as room label. |
| 11–12 Scheduler and professors | Weekly timetable, period/program/year/section/floor/room/day filters, drag/edit meeting, qualified professor selection with availability text | Browser scheduler checks; server conflict, availability and capacity tests. Referenced meetings require a new offering and revised proposals for historical safety. Period combines academic year and semester. |
| 13 Special evaluations | Structured requested subjects; each equivalency records original school, subject, grade, units, evidence reference, reason, decision and linked credit record | Browser request/evaluation flow; tests for missing evidence, duplicates, out-of-curriculum credits, rejected credits, permissions and atomic rollback. Evidence is a document reference, not a file-upload store. |
| 14–15 Enrollment and decisions | Versioned special/final proposals, remarks on decline, latest-special acceptance check, retained accepted special assignments, Registrar finalization | Browser irregular flow completed through both student acceptances and finalization. Tests cover regular flow, stale decisions, prerequisites and capacity. |
| 16 COR | Finalized-only HTML/print/PDF, immutable version snapshots, authorized reopen/re-finalize, historical copies | PDF/history tests and browser COR. Uses a PLP text mark; official logo and approved COR template have not been provided. |
| 17 Authentication | Hashing, login throttling, session logout, role checks, single-use recovery, temporary-password enforcement, token expiry/revocation | Automated security-behavior tests; includes import API first-login guard. Actual email delivery is unverified (local log mail driver). |
| 18–19 Notifications and dashboards | Role/classification and section totals, scoped evaluation counts, student information/proposals/weekly schedule/COR, private notices, audit/activity views, filtered enrollment CSV | Rendering, privacy and CSV safety tests. No external notification service is configured. |
| 20 Database | Relational migrations with separate academic, evaluation, scheduling, enrollment, credit and history tables | Foreign keys and unique constraints; the DOCX table list is a suggested schema, not a requirement to duplicate equivalent tables. |
| 21–23 Technology, tools, Git, architecture | Laravel 12, PHP, MariaDB, Blade, Bootstrap 5, actual jQuery, layered services/controllers, migrations, local Git checkout | Portable PHP/MariaDB used instead of XAMPP. Vendored frontend assets do not require Vite. No GitHub remote was supplied or published. |
| 24 Proposed API | Auth, student records, curricula/subjects, prerequisites, sections, rooms/professors availability, offerings/schedules, proposals/COR and special-case actions | Added missing aliases and API tests. Availability returns recorded windows, meetings and closures; final schedule writes remain authoritative. |
| 25–27 Responsive interface, tables, jQuery | Responsive navigation/tables, paginated server search, modal academic editing, dynamic prerequisite/section/professor choices, proposal unit totals, AJAX scheduler | Browser evaluation screen checked at all four specified widths; modal/scheduler checked for errors. Tables use stable default ordering; arbitrary per-column sorting is not provided everywhere. |
| 28 Testing | Automated workflow, API, validation, isolation and concurrency tests; targeted browser and responsive checks | See TESTING.md. These do not replace an independent penetration test, real-data load test or school user acceptance. |
| 29–32 Delivery and success | Runnable local portals, source, schema/migrations, docs and launcher | Local functional testing is complete for the scenarios recorded. Real integrations, official policy/template confirmation, remote repository, production hosting and institutional sign-off remain outstanding. |

## Workflow decisions

Section 14.2 describes two student acceptance stages for special cases, while the shorter transferee/returning descriptions omit that detail. The application consistently follows 14.2 for all special classifications. PLP should approve this interpretation before operational use.

Approved special proposals are visible to the Registrar through shared scoped records and notifications. The Department Head also has an explicit forward action that records a timestamp and sends a notice; it does not create enrollment or replace either student acceptance.

Credited courses are separate academic records, not current-semester enrolled classes. An approved equivalency does not itself finalize enrollment or create a COR. A curriculum reassignment preserves existing credits and previous CORs; evaluations tied to an old curriculum cannot authorize new proposals under a new curriculum.

## Outstanding external inputs

- Actual Admission and EMS export/API contracts and authorized access.
- Official grade direction, credit rules, any unit-limit or overload policy, and confirmation of role/program assignments.
- Approved curriculum, academic history source, campus facilities and professor availability.
- Official institution logo/COR template and institutional workflow approval.
- SMTP delivery settings, production hosting/HTTPS/backup operations, GitHub repository destination, and school acceptance/load testing.

Do not interpret passing tests as proof that all possible inputs or every production requirement have been verified.

## October 31 completion update

The Laravel app now has a working local MariaDB runtime/launcher and verified SQLite-to-MariaDB transfer. Professor search/filter/sort, visible teaching/availability details and future EMS synchronization timestamps were added. Scheduler year/semester filters, an available-professors panel and non-persisting validation preview now precede confirmation; saving still revalidates on the server. Subject forms can select prerequisites atomically with cycle checks. Core lists support allowlisted server sorting and more consistent filters/status display. Registrar settings control the institution name, grade comparison and default section capacity. New COR snapshots retain an explicit academic year, semester and the supplied institution seal.

The supplied EnrollmentSystem directory provided the seal, branding provenance, program list and separately labeled 2022–2023 curricula. Existing Laravel students retained their assignments; Node data and conflicting registration rules were not imported. The local grading direction follows that source's lower-is-better scale. Older COR snapshot payloads remain unchanged.

Encrypted backups and isolated restore verification were implemented and exercised locally. Institutional SMTP/API acceptance, approved current policies/curriculum/COR form, repository destination and production operations remain school inputs. Refer to INSTITUTION-HANDOFF.md and LOCAL-SETUP.md for concrete setup and remaining checks.
