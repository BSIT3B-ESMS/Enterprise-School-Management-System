# Pamantasan ng Lungsod ng Pasig — Online Admission System

Responsive frontend for the PLP online admission system. It has two separate sides:

- **Applicant portal** (`index.html`): a 6-step admission journey (application form, documents, review, exam, result, medical requirements), plus the application form, document uploads, and profile.
- **Admission office panel** (`admin/index.html`): for admission personnel. It covers review queues, document verification with remarks, exam sessions (Batch A morning, Batch B afternoon) with rosters and attendance, recording and releasing results, medical clearance for applicants who passed, reports with CSV export, programs/slots/requirements, and a read-only audit log.

## Run locally

Serve the project folder with a local web server (for example XAMPP at `http://localhost/Admission_System/`), then open:

- `http://localhost/Admission_System/` for the applicant portal
- `http://localhost/Admission_System/admin/` for the admission office

Bootstrap, jQuery, and the fonts load from CDNs, so the browser needs an internet connection for those assets.

## Project structure

```
index.html                     Applicant portal
admin/index.html               Admission office panel
assets/
  shared/                      Used by both sides
    css/core.css               Tokens, base, buttons, forms, sign-in, sidebar shell, modals, toasts
    js/core.js                 window.AO: icons, storage, dates, toast, validation, sidebar helpers
  applicant/
    css/applicant.css          Applicant-only styles
    js/applicant.js            Applicant portal logic
  admin/
    css/admin.css              Admin-only styles (tables, KPIs, charts, rosters, print)
    js/admin-store.js          Config (statuses, documents, programs, barangays), demo data, state
    js/admin-actions.js        Every admin change; each one writes to the audit trail
    js/admin-ui.js             Shared admin UI: pills, charts, pager, modals, printing
    js/views/*.js              One file per screen (dashboard, applicants, documents, exams,
                               results, medical, reports, settings, audit)
    js/admin.js                Sign-in, hash router, boot (loaded last)
```

Load order matters: `core.js` comes first, then the side-specific scripts. In the admin panel, views register on `ADM.views` and `admin.js` boots last.

## Prototype scope

This is a frontend prototype. Both sides keep their data in the browser (`localStorage`), and the two sides don't share data yet. Sign-in, uploads, verification, exam scheduling, and result release are simulated. Search the code for `BACKEND:` comments that mark where the Laravel API calls (JWT auth, RBAC, MySQL) go.

In the admission office, use **Programs & requirements → Reset demo data** to restore the sample applicants.
