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
