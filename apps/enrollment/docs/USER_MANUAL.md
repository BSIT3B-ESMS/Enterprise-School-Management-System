# Local user guide

## Launch

Open PowerShell in the project folder and run:

```powershell
powershell -ExecutionPolicy Bypass -File .\start.ps1
```

Open http://127.0.0.1:8000. The script starts the configured local MariaDB server, applies migrations and runs Laravel on loopback only. It does not publish the site. The launcher runs the web server in the background and discovers the installed PHP runtime. See LOCAL-SETUP.md for setup and operations.

## Accounts supplied by the institution

Sign in using your provisioned account. Student accounts originate from the other group's Admission system through the authenticated API or approved JSON exchange. There is no public registration or first-account setup screen, and no preset login credentials. Coordinate the actual Admission contract and credential handoff with that group. Registrar and Department Head access must use the authorized staff provisioning process; an Admission student import cannot grant staff roles.

The active database is the private MariaDB/MySQL target configured in .env; the SQLite source remains archived. The earlier sample database remains archived. No fictional students or employees are active.

## Registrar

Use Academic management to create programs and periods, curriculum versions, subjects, curriculum requirements and prerequisites. Prerequisite alternative group zero means all listed prerequisites are mandatory; each positive group requires at least one completed option. Completion is determined by PASSED or CREDITED status. Numeric passing-grade policies are not assumed.

Import Admission and EMS JSON through System imports. For Admission choose an active curriculum matching program_code. New students without temporary passwords receive the password-setup recovery email. With the default local log mail driver, messages are captured in storage/logs rather than delivered to an inbox. Configure SMTP for real delivery. Uploaded passwords are hashed and excluded from application import/audit logs. Do not keep plaintext exports in the project.

Use Academic records to view a student and set regular, irregular, transferee or returning classification. Maintain prior records and credited subjects through Academic management -> Academic records, using remarks to identify the authorized academic source or equivalence approval. Academic records are not imported from Admission automatically.

Create buildings and floors, then sections, rooms, EMS teaching authorizations, professor availabilities, offerings and schedules before proposals. A professor must be active, authorized for the subject, and available for the full meeting. Class capacity cannot exceed section or room capacity. Room type must match subject type; physical_education supports the gym use case. Record room maintenance or activity restrictions under Room closures.

The Scheduler supports filtering by period, program, year level, section, floor, room and day; dragging offerings onto day/hour slots; and moving or editing existing unreferenced meetings. Changes are confirmed through the meeting form and validated by jQuery AJAX against the server. Keyboard users can fill the form directly. Existing meetings can be edited through Academic management -> Schedules -> Edit before proposal history exists. Once referenced by a proposal, create a new offering with the revised schedule and send revised proposals. The system preserves the schedule students reviewed.

Prepare a proposal from Enrollments. Select a student, period and offerings. Students can accept or request revision with remarks. Student acceptance immediately finalizes the enrollment and generates its COR. Pending proposals do not reserve seats, so finalization may reject a class that filled meanwhile. Choose another offering and send a revised proposal.

A finalized enrollment provides printable HTML and a downloadable server-generated PDF COR. To change finalized enrollment, enter the reason and use Reopen enrollment for revision. Seats are released, prior COR snapshots remain in the database, and the replacement proposal needs fresh student acceptance. Use COR versions on the enrollment page to view or download saved versions. Superseded versions are labeled historical copies.

Create authorized staff accounts through Staff accounts. Assign Department Heads to programs through Academic management -> Department head programs. Without assignments a Department Head cannot access student records. Initial Registrar setup is also available through php artisan enrollment:create-staff.

## Department Head

Review only students and requests in assigned programs. Review the academic record and required curriculum. Approve or reject pending special-case evaluations with reasons. For irregular, transferee and returning students, prepare the special academic proposal after approval. Use the approved evaluation schedule builder to send directly to the student or forward a draft to the Registrar. Forwarded drafts are published from Special enrollment; the Registrar must retain the reviewed assignments. The student accepts the published proposal once, which finalizes enrollment and creates the COR.

Authorized prerequisite exceptions can be recorded on a student's academic record page. They require a reason, an approving Department Head, and a period. They do not bypass curriculum membership, repeated completed subjects, schedule conflicts, or capacity checks.

## Student

Use the Admission-provisioned account. Set a password from the invitation or change the temporary password at first login. View Academic records for completed and still-required subjects. Request back subjects or special evaluation through Academic evaluations. Review each proposal's subjects, units, sections, professors and meetings. Acceptance records the exact version. Request revision with remarks if changes are needed. Download the COR immediately after successful acceptance.

Notifications shows enrollment and academic-evaluation events. Open details marks a notification as read. Activity shows the decision history. Account settings provides password change, and the login page provides password recovery.
Minimum-grade prerequisites use GRADE_ORDER=lower or higher. Confirm the institutional policy before setting it. An unconfigured direction blocks threshold-dependent eligibility. Required/elective flags are maintained in curriculum requirements. Dedicated rooms can specify a single subject; shared rooms leave that field empty.

Enrollment reports supports period, program and status filters and a CSV export. The export neutralizes spreadsheet-formula prefixes. Staff accounts can be activated or deactivated; deactivation revokes sessions and API tokens. A Registrar cannot deactivate their own account.

Curriculum versions already assigned to students preserve their historical requirements. Create a new curriculum version to change those requirements. Inactive academic records can be reactivated when validation permits; schedule/offerings with proposal history retain stricter revision controls. A regular proposal must include all still-required subjects prescribed for its year and semester. Special academic loads require the evaluation workflow.

## Structured evaluations and curriculum history

Students can select specific subjects when requesting evaluation. Open Academic evaluations → View evaluation and subject credits. The Department Head reviews academic records, adds subject equivalency rows (original institution/subject/grade/units, evidence reference, equivalent PLP subject, decision and reason), and approves or rejects the evaluation. Approved rows create CREDITED academic records atomically; rejected rows stay in evaluation history without awarding credit. Use synthetic data when practising. The evidence field references an official record; it does not upload a transcript.

Prepare the special enrollment proposal from Enrollments. After the student accepts, the Department Head can use Forward accepted proposal to Registrar. The Registrar’s final proposal must retain the accepted special class assignments. Changing those assignments requires a new Department Head proposal and fresh student acceptance.

For a verified returning student, the Registrar opens Academic records → student → Assign a different curriculum version. Prepare the new version and requirements first, choose it within the same program and record the verification/reason. Resolve all open proposals first. The original curriculum requirements, academic records and saved CORs remain available. A new evaluation is required for a special student after reassignment.

Curriculum Management → View units shows semester and total units. Academic record editing uses a Bootstrap modal. The scheduler shows qualified professors and their recorded availability; backend checks still run on every save.

## Calendar, sections and year-end

Open Semester & pipeline. Create a semester, set nonoverlapping dates, and open it. Active students are assigned primary sections in admission order, opening another section when capacity is reached. Newly imported students receive the same assignment process. Students and Admission year provide college/program/year/section/cohort filters and batch actions.

Verify each subject’s weekly contact hours separately from its units. Use the room-by-hour scheduler to drag or tap subjects into rooms and hours, choose an available qualified professor, check the booking and confirm it. Finalize the complete year-level schedule through the calendar, then select students and send section proposals. Students review the exact version and accept or request revision.

Finish the semester through the calendar to retain its schedules, grades and CORs and prepare the next semester. After finishing second semester, review all grades and promote selected students once for the academic year before opening the next year. No historical schedules or registration snapshots are deleted.

## Back subjects and waiting lists

Special-classification students use Subject requests to select remaining requirements. Eligible offered requests go to Department Head review; unavailable or prerequisite-blocked subjects wait. Semester activation, room-grid booking and grade posting recheck waiting subjects. Heads can defer a pending request when all its subjects are unavailable. Approved evaluations show fixed primary assignments, requested additions and a weekly preview.

## Professor

The Registrar creates authorized Professor access linked to an active imported EMS professor. The portal shows only assigned classes and finalized rosters. Submit official transmuted grades: 1.00–3.00 passed, 5.00 failed, INC incomplete. Failed/incomplete results mark regular students irregular. Students see results in Grades. Closed-semester grades are view only for Professors.

See REFERENCE-INTEGRATION.md for the source HTML flow mapping and operational adaptations.
The seven-stage pipeline links directly to matching students. Academic year is separate from Admission year. Sections supports sending or promoting everyone in the filtered sections. Special enrollment shows subject/unit totals and opens a preselected proposal composer. Grades includes totals, prerequisites and Upcoming subjects. My account includes read-only academic or EMS details for the signed-in role.
