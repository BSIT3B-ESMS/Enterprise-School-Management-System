# Integration contract and HTTP interfaces

The application provides authenticated session routes with Laravel CSRF protection and a bearer-token REST API under /api/v1. These are Enrollment System endpoints, not known live endpoints belonging to Admission or EMS. Approved structured-file exchange is also available through the Registrar-only /imports screen. Both paths call the same ImportService.

## Admission file envelope

```json
{
  "source": "admission",
  "schema_version": "1.0",
  "students": [
    {
      "admission_id": "DEMO-ADM-001",
      "first_name": "Example",
      "last_name": "Student",
      "email": "example@example.test",
      "program_code": "DEMO-IT",
      "year_level": 1,
      "admission_status": "ADMITTED"
    }
  ]
}
```

Select the curriculum in the import form. middle_name and temporary_password are optional. Omitted temporary_password uses a password-setup invitation via the configured mail driver. A supplied temporary password must have at least 12 characters and requires change after first login. New records are matched by admission_id. Existing records update eligible name/email fields and preserve credentials and academic identity. Conflicting unique email addresses reject the record. Grades and back subjects are not taken from Admission.

## EMS file envelope

```json
{
  "source": "ems",
  "schema_version": "1.0",
  "employees": [
    {
      "employee_id": "DEMO-EMP-002",
      "first_name": "Example",
      "last_name": "Professor",
      "email": "professor2@example.test",
      "department_code": "DEMO",
      "employment_type": "FULL_TIME",
      "employment_status": "ACTIVE"
    }
  ]
}
```

Employment type supports FULL_TIME/PART_TIME. Employment status supports ACTIVE/INACTIVE. Matching uses employee_id; updates preserve teaching authorization and availability managed locally. An optional teaching_assignments array containing subject_code values synchronizes teaching authorizations by matching active local subjects. Omitted teaching_assignments preserves existing authorizations; an empty array deactivates them. Unknown subject codes reject the employee row. Confirm whether EMS owns this field before enabling it with real exports. Imported inactive employees cannot be used to finalize or schedule new assignments.

## Import validation

2 MB and 1,000-record limits. Envelope source and schema version must match. Records are processed transactionally, one row at a time. Failed records roll back without affecting valid rows. Import logs include received, created, updated, rejected and sanitized row validation labels. Raw exception text, passwords, file contents and private payloads are not written to import/audit logs.

## Primary session endpoints

- POST /login and /logout
- POST /account/password, /forgot-password, /reset-password
- GET/POST /academic/{resource}; POST /academic/{resource}/{id}; POST /academic/{resource}/{id}/deactivate
- GET /records and /records/{id}; Registrar POST /records/{id}
- GET/POST /enrollments; GET /enrollments/{id}
- Student POST /enrollments/{id}/decision with version, decision accept/decline, remarks
- Registrar POST /enrollments/{id}/finalize and /enrollments/{id}/revise
- Authorized GET /enrollments/{id}/cor and /enrollments/{id}/pdf
- GET/POST /special-cases; Department Head POST /special-cases/{id}/evaluate
- Department Head POST /academic-overrides
- Registrar GET/POST /imports and /staff
- GET /notifications; POST /notifications/{id}/read

All modifying session requests require a CSRF token. Laravel returns validation failures with errors and preserves appropriate non-sensitive input. Role and ownership checks run server-side. Department Head access also requires a current program assignment. Real API authentication, callbacks, field mapping, service availability and privacy terms must be agreed with the external teams before live synchronization.
## Bearer authentication and API routes

POST /api/v1/auth/login with email/password issues an eight-hour portal token. Use Authorization: Bearer TOKEN and Accept: application/json. Password setup is enforced before portal data access. POST /auth/logout revokes the current token; POST /auth/change-password verifies the current password, changes it, and revokes tokens and existing sessions. /auth/forgot-password and /auth/reset-password support recovery.

Integration-only tokens are created locally with php artisan enrollment:integration-token admission --user=ID or source ems. The selected account must be an active authorized Registrar service account. Tokens expire after 30 days and grant only import:admission or import:ems, with no portal access. Never place tokens in URLs, source files or ordinary logs.

POST /api/v1/integrations/admission or /integrations/ems with the JSON envelope. Admission requests also include curriculum_id at the envelope level. Portal Registrar tokens can import either source; source-only tokens can import only their specified source. The JSON response includes received, created, updated, rejected, sanitized errors and log_id.

API student, academic-record, curriculum and back-subject reads enforce ownership/program boundaries. Enrollment reads, proposals, approve/decline with version, finalization, special-case evaluation and COR PDF downloads share the web services and role checks. Registrar resource reads include programs, curriculums, subjects, sections, rooms, professors, class-offerings and class-schedules. POST /academic/{resource} and PUT /academic/{resource}/{id} expose validated academic maintenance, with convenient POST aliases for core resources. Use php artisan route:list --path=api to inspect the authoritative route list.

A revoked, inactive or expired token cannot access data. Integration endpoints are rate limited and enforce the same 2 MB / 1,000-record bounds as file imports. For real external calls use HTTPS, dedicated service accounts, an agreed schema/version lifecycle and protected token delivery. No live source credentials were available for connectivity verification.

## API additions in the Desktop-folder update

The proposed subject PUT/DELETE, section PUT and class-schedule PUT aliases are implemented. DELETE /subjects/{id} deactivates, preserving history. GET/POST /subjects/{id}/prerequisites shares backend cycle validation; POST accepts required_subject_id, alternative_group and optional minimum_grade.

Registrar GET /rooms/{id}/availability and /professors/{id}/availability require period_id and accept optional day (Mon–Sun). They return active status, available_windows for professors, scheduled_meetings and closures for rooms. They do not guarantee a proposed assignment will pass every academic constraint.

GET /special-cases/{id} enforces student ownership and Department Head program scope. POST /special-cases supports requested_subject_ids. Department Head POST /special-cases/{id}/evaluate accepts status approved/rejected and evaluation, with optional credits array. Each row requires subject_id, source_institution, source_subject, source_units, evidence_reference, reason and decision approved/rejected; source_grade is optional. /approve and /reject are aliases fixing status. /forward-to-registrar requires an approved current-curriculum evaluation and student acceptance of the current special proposal.

Registrar POST /students/{id}/curriculum accepts previous_curriculum_id, curriculum_id and reason (20–3000 characters). It rejects stale assignments, empty/inactive/cross-program curricula and open enrollment proposals. The session equivalent is POST /records/{id}/curriculum.

Integration endpoints also enforce the first-login password-change requirement. Service accounts must complete account setup before importing.

## HTML workflow integration update

Student decision acceptance now atomically finalizes the current enrollment and creates the COR. The legacy Registrar finalize endpoint is idempotent for already-finalized records. Capacity/eligibility failures roll back the decision. A staged Department Head draft (special_ready) cannot be accepted until Registrar publishes a student proposal.

New session interfaces: Registrar GET/POST /term-calendar, POST /term-calendar/{id} with dates/activate/lock/close; GET /students and /admission-years, POST /students/actions; GET/POST /sections, POST /sections/{id}; GET /colleges; GET/POST /special-enrollment; GET /scheduler/rooms and POST /scheduler/book (preview=1 validates without persisting). Head GET /special-students, POST /special-cases/{id}/defer and /forward-to-registrar (action=send or forward, offering_ids). Student GET /grades and GET/POST /waiting-list. Professor GET /professor, POST /professor/classes/{id}/grades with a student-ID-keyed grades object. These are CSRF-protected session endpoints; no new bearer aliases are implied.

Professor portal tokens cannot enumerate general academic/student API resources. Professor grade writes require an active EMS link, a recorded teaching assignment, a finalized class roster and an open semester. See REFERENCE-INTEGRATION.md for all flow mappings.