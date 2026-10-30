# Local user guide

## Launch

Open PowerShell in the project folder and run:

```powershell
powershell -ExecutionPolicy Bypass -File .\start.ps1
```

Open http://127.0.0.1:8000. The script starts the portable MariaDB server when the local port 3307 is configured, applies migrations, and runs Laravel on loopback only. It does not publish the site. Stop the web server with Ctrl+C. MariaDB stays available for subsequent local launches. Runtime paths in start.ps1 are specific to this computer; update them when moving the project.

## Demo accounts

All records with DEMO labels and all example.test identities are synthetic. They are for local testing only. The local seed includes Registrar, Department Head, regular Student and irregular Student accounts. Shared demo password: DemoEnrollment2026!.

- registrar@example.test
- department_head@example.test
- student@example.test
- irregular@example.test

These demo accounts skip the mandatory temporary-password change to make demonstrations repeatable. Real imported and staff-created accounts require password setup or first-login password change. Never deploy the demo database or these credentials to a real institutional environment.

## Registrar

Use Academic management to create programs and periods, curriculum versions, subjects, curriculum requirements and prerequisites. Prerequisite alternative group zero means all listed prerequisites are mandatory; each positive group requires at least one completed option. Completion is determined by PASSED or CREDITED status. Numeric passing-grade policies are not assumed.

Import Admission and EMS JSON through System imports. For Admission choose an active curriculum matching program_code. New students without temporary passwords receive the password-setup recovery email. With the default local log mail driver, messages are captured in storage/logs rather than delivered to an inbox. Configure SMTP for real delivery. Uploaded passwords are hashed and excluded from application import/audit logs. Do not keep plaintext exports in the project.

Use Academic records to view a student and set regular, irregular, transferee or returning classification. Maintain prior records and credited subjects through Academic management -> Academic records, using remarks to identify the authorized academic source or equivalence approval. Academic records are not imported from Admission automatically.

Create buildings and floors, then sections, rooms, EMS teaching authorizations, professor availabilities, offerings and schedules before proposals. A professor must be active, authorized for the subject, and available for the full meeting. Class capacity cannot exceed section or room capacity. Room type must match subject type; physical_education supports the gym use case. Record room maintenance or activity restrictions under Room closures.

The Scheduler supports filtering by period, program, year level, section, floor, room and day; dragging offerings onto day/hour slots; and moving or editing existing unreferenced meetings. Changes are confirmed through the meeting form and validated by jQuery AJAX against the server. Keyboard users can fill the form directly. Existing meetings can be edited through Academic management -> Schedules -> Edit before proposal history exists. Once referenced by a proposal, create a new offering with the revised schedule and send revised proposals. The system preserves the schedule students reviewed.

Prepare a proposal from Enrollments. Select a student, period and offerings. Students can accept or request revision with remarks. Finalize only an accepted final proposal. Pending proposals do not reserve seats, so finalization may reject a class that filled meanwhile. Choose another offering and send a revised proposal.

A finalized enrollment provides printable HTML and a downloadable server-generated PDF COR. To change finalized enrollment, enter the reason and use Reopen enrollment for revision. Seats are released, prior COR snapshots remain in the database, and the replacement proposal needs fresh student acceptance. Use COR versions on the enrollment page to view or download saved versions. Superseded versions are labeled historical copies.

Create authorized staff accounts through Staff accounts. Assign Department Heads to programs through Academic management -> Department head programs. Without assignments a Department Head cannot access student records. Initial Registrar setup is also available through php artisan enrollment:create-staff.

## Department Head

Review only students and requests in assigned programs. Review the academic record and required curriculum. Approve or reject pending special-case evaluations with reasons. For irregular, transferee and returning students, prepare the special academic proposal after approval. The student accepts that special proposal, then the Registrar sends the complete final proposal for a second acceptance. This consistent two-stage flow is the implementation assumption pending PLP confirmation for transferees and returning students.

Authorized prerequisite exceptions can be recorded on a student's academic record page. They require a reason, an approving Department Head, and a period. They do not bypass curriculum membership, repeated completed subjects, schedule conflicts, or capacity checks.

## Student

Use the Admission-provisioned account. Set a password from the invitation or change the temporary password at first login. View Academic records for completed and still-required subjects. Request back subjects or special evaluation through Academic evaluations. Review each proposal's subjects, units, sections, professors and meetings. Acceptance records the exact version. Request revision with remarks if changes are needed. Download the COR after Registrar finalization.

Notifications shows enrollment and academic-evaluation events. Open details marks a notification as read. Activity shows the decision history. Account settings provides password change, and the login page provides password recovery.
Minimum-grade prerequisites use GRADE_ORDER=lower or higher. Confirm the institutional policy before setting it. An unconfigured direction blocks threshold-dependent eligibility. Required/elective flags are maintained in curriculum requirements. Dedicated rooms can specify a single subject; shared rooms leave that field empty.

Enrollment reports supports period, program and status filters and a CSV export. The export neutralizes spreadsheet-formula prefixes. Staff accounts can be activated or deactivated; deactivation revokes sessions and API tokens. A Registrar cannot deactivate their own account.
