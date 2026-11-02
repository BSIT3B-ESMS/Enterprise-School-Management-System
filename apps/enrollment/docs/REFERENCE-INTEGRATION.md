# Supplied HTML workflow integration

Reference: `C:/Users/medin/Downloads/PLP_Enrollment_Management_System.html`. Its content is reference material, not permission to execute its reset routines or import fictional identities. The Laravel app and MariaDB/MySQL remain authoritative. The user explicitly replaced the earlier SQLite-only decision.

## Confirmed decisions

Student acceptance finalizes enrollment and creates the COR in the same database transaction. Primary sections fill automatically and open the next label when full. Professors receive a separate portal restricted to their linked EMS teaching assignments. Student accounts continue to originate in Admission; no public registration was added.

## Flow mapping

| HTML surface / action | Laravel implementation |
| --- | --- |
| Login, role shell and account | Existing hashed Laravel authentication, first-login password change, complete read-only Student (Admission ID, college, program, year, classification, section, status) and Professor (employee ID, employment, department, authorized subjects) details, Head program scope and role-specific navigation. No demo account selector. |
| Registrar dashboard / enrollment pipeline | `/registrar` overview plus `/term-calendar`; proposals, forwarded drafts, revision requests and finalized results link to enrollment lists. All seven source pipeline columns are present and link to the matching students. Accepted is a cumulative milestone including Finalized, because acceptance creates the COR atomically. |
| Current semester, calendar dates, finish semester/year, next cycle | `/term-calendar`; dates validated for ordering and overlap, activate assigns sections, close expires outstanding offers and prepares the next semester with dates unset. Closed schedules/CORs remain readable. |
| Students by college/program/year/section | `/students`; search, college/program/year/classification/section/unassigned filters, program/year summaries, record and enrollment links. |
| Academic year | `/academic-years`; academic-year program/section/student/year summaries and search use recorded memberships and retain historical year standing after promotion. |
| Admission year | `/admission-years`; explicit admission cohort distinct from academic semester and year standing. Admission ID is retained. |
| Auto-assign / open section / capacity | Semester activation and new Admission import call `TermWorkflowService`; deterministic admission order, section membership capacity and generated A/B/... labels. `/sections` manages capacity and membership links. |
| Bulk section proposals / bulk year promotion | Selected students in `/students`, plus send/promote-all for filtered sections in `/sections`; each row validates independently. Section publishing requires the year-level schedule lock. Promotion requires a closed second semester, all year requirements graded/credited, no unresolved proposal and a unique student/year promotion record. |
| Colleges and programs | `/colleges` and `/academic/colleges`, `/academic/programs`; create/rename/attach/deactivate. Programs with students/section history cannot be removed; colleges with active programs cannot be deactivated. |
| Subjects, general/college/program grouping, prerequisites | `/academic/subjects` scope filters and scope links, separate weekly contact hours, room types, allowed days and existing integrated prerequisite editor. Curriculum requirements retain program/year/semester authority and historical versions. |
| Room-by-hour day grid and subject chips | `/scheduler/rooms`; college/year/section/day filters, scheduled-hour progress, drag or tap into room/time, qualified available professor selection, server preview and confirmed save. Preview rolls back temporary offering creation. |
| Weekly schedule editing, conflicts and room availability | `/scheduler`; existing room/professor/section conflict checks, room closures, teaching qualifications and availability; history-bearing schedules cannot be overwritten. |
| Finalize all year-level schedules | Calendar finalization checks active sections’ prescribed subjects and each offering’s exact verified weekly hours, validates meetings, then locks the semester/year for edits. |
| Rooms and professors | `/academic/rooms`, buildings/floors/closures and `/professors`; EMS imports supply employee identities. Staff accounts can link one active imported professor to one Professor login. |
| Department Head special-student queue | `/special-students` and `/special-cases`; program-scoped irregular/transferee/returning records, official-record review, structured credit decisions with evidence. |
| Head schedule builder / fixed primary classes / requested extras | Approved evaluation detail; eligible primary-section assignments retained, additional requested class checkboxes, drag/tap weekly preview; server revalidates allowed selections, prerequisites, duplicates, capacity and conflicts. |
| Head direct-send versus forwarding to Registrar | Evaluation builder has both actions. Direct-send awaits one student acceptance. Forwarded drafts use `special_ready`, cannot be accepted by the student, and are published by Registrar retaining Head-approved assignments. |
| Special enrollment / bulk send | `/special-enrollment`; semester/program/year filters, review links, select-all and per-row validated publishing of forwarded drafts. Individual proposals can also be prepared through Enrollments. |
| Defer all-unavailable requests | Head evaluation detail can defer a pending request only when every requested subject is unavailable/ineligible. The request is preserved; subjects enter the waiting list. |
| Student subject requests / back subjects / re-enrollment | `/waiting-list`; remaining requirements and availability reasons, request remarks, ready subjects sent for Head evaluation, unavailable or prerequisite-blocked subjects retained. A finalized semester cannot acquire another COR; new requests use the next open semester. |
| Automatic waiting release / notifications | Semester activation, grade submission and room-grid saves recheck waiting subjects and create/extend a pending scoped evaluation when eligible. Private notifications link to each recipient’s authorized screen. |
| Student proposal acceptance / decline / COR | Existing enrollment review plus weekly timetable. Decline requires remarks; only latest published version can be accepted. Acceptance rechecks constraints, reserves seats and stores an immutable COR exactly once. HTML/PDF, printing and historical COR versions remain available. |
| Professor grades and student grade view | `/professor`, `/grades`; grade totals, prerequisites, upcoming/no-grade labels, submitter, semester and assigned finalized roster only; 1.00–3.00 passed, 5.00 failed, INC incomplete. Failed/INC marks regular students irregular and notifies authorized recipients. Closed semesters reject Professor edits. |
| Admission/EMS JSON import and logs | `/imports` accepts a file or pasted envelope; same schema/version, size, row transaction, authorization and sanitized logging as existing authenticated integration APIs. Import never grants a student staff privileges. |
| Audit and activity | Existing `/audit`, `/activity` and immutable enrollment events capture calendar, assignment, publishing, grading and revision actions. |

## Operational adaptations

- Prototype `localStorage`, SHA passwords, account picker, fictional seed records and destructive reset controls were not imported. The supplied HTML is unchanged.
- Closing a year archives schedules instead of deleting academic history. Reopening a finalized enrollment is an explicitly audited Registrar revision and is blocked for closed semesters.
- Contact hours must be verified in Subjects; academic units are not silently assumed to equal teaching hours. Admission/EMS contracts, live connectivity, current curriculum and actual campus records still require institutional inputs.
- The supplied curricula are historical 2022–2023 references. Creating newer program requirements uses existing curriculum versioning; imports do not silently reassign students.
- Existing legacy periods without calendar activation retain compatibility with older proposal endpoints. For a new operational term, open it through the calendar, complete and finalize its schedules, then publish section proposals.

## Verification

Run `php scripts/test.php` using the configured PHP runtime. The suite uses memory/isolated SQLite files, including competing student acceptances for one remaining seat. Browser verification uses a separate disposable MariaDB QA database. Previous checks covered acceptance → immediate COR and assigned Professor grades; the completed pass also checks the seven-stage pipeline, its student links, grade summaries/prerequisites/upcoming labels and complete read-only account details. The operational database receives no QA records. Desktop rendering was inspected; the browser viewport override did not take effect, so a new mobile screenshot was not verified in this pass.

An encrypted SQLite backup was created before migration 000013, and a fresh MariaDB transfer later preserved and compared 39 application tables with foreign-key verification. MariaDB encrypted dumps are restored and verified independently. Migration retains existing identities, catalog entries, proposals and snapshots, adding only college metadata and workflow tables/columns. Rollback with actual Professor users requires a reviewed data migration; use verified backups and maintenance procedures rather than dropping populated academic tables.

## Source action audit completed October 31, 2026

The source HTML's event handlers were inventoried alongside its screen functions. The following table groups every event control by its Laravel destination; controls may use native forms rather than the prototype's IDs.

| Source controls | Implemented destination / behavior |
| --- | --- |
| lg, lo, nav links, q, pw, bk/bk2 | Hashed sign-in, role navigation, logout, allowlisted searches, password change and return links. |
| bld, send, rej, csv | Enrollment composer, direct/forwarded Head builder, approve/reject evaluation, structured evidenced credit decisions. Registrar drafts retain reviewed items and show selected subjects/units. |
| acc, dec, fin, cor-b | Latest-version accept/finalize, decline with remarks, idempotent legacy finalization, immutable current/historical printable/PDF COR. |
| rq, rqo/rqc/rqs, rre | Student request selection, submit/cancel by navigation, waiting reasons, next-semester re-enrollment and a new term's COR. |
| ns, pre, tog, ned, sy2/sm, sbb | Subject editor, prerequisites, activation/deactivation, cancel edit, curriculum year/semester requirements and general/college/program scopes. Shared-curriculum prerequisites must precede the target term. |
| as, cp, student section as, sall, ssend, pro, spro | Create section, capacity edit, primary assignment, filtered section-wide publish/promotion and per-student/bulk controls with grade, period and duplicate-promotion guards. |
| rt, ar, ac, ap, prm, cren | Room availability and create/edit, college create/rename, program attach/deactivate with historical dependency guards. Room availability changes preserve issued COR snapshots. |
| ss, sd, chip/dz, pcx/psv, ph, rm, sfin, tsv | Section/day selection, drag/tap room bookings, preview/cancel/confirm, qualified available professors, deactivate unreferenced meeting, finalized schedule locks and semester dates. |
| scb/syb, svb, syf/spf, sga/sgs | College/year/section and special-enrollment filters, select-all and per-row validated batch publishing. |
| kb, dch/sg, dx, dsend, ddef | Scoped classification queue with requested subjects and evaluation links; fixed primary timetable, selectable/draggable requested additions, deselection, direct publish or Registrar-forwarded draft, all-unavailable defer. |
| ayb, acb, smb, fin/finx, asm/ssm | Admission cohort and academic-year views, semester controls and explicit-confirmation closure preparing the next term while preserving history. |
| gcb, gsub, ib | Assigned Professor class selection/grade submission; file or pasted Admission/EMS JSON exchange with result counts and sanitized log. |

The source's client-side storage and authentication, seeded identities, destructive schedule deletion at year-end and direct unaudited credit toggles are replaced by equivalent persisted, authorized Laravel workflows. This follows the existing no-demo, Admission-account and historical-record instructions. Schedule/COR history is retained; schedule changes with proposal history use the controlled revision/new-offering workflow. Actual institutional record imports and external connectivity remain separate from feature implementation.