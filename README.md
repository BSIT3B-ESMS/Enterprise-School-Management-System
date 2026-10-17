# Pamantasan ng Lungsod ng Pasig — Online Admission System

Responsive frontend for the PLP online admission system. It has two separate sides:

- **Applicant portal** (`/`, view `resources/views/applicant.blade.php`): a 6-step admission journey (application form, documents, review, exam, result, medical requirements), plus the application form, document uploads, and profile.
- **Admission office panel** (`/admin`, view `resources/views/admin/index.blade.php`): for admission personnel. It covers review queues, document verification with remarks, exam sessions (Batch A morning, Batch B afternoon) with rosters and attendance, recording and releasing results, medical clearance for applicants who passed, reports with CSV export, programs/slots/requirements, and a read-only audit log.

## Run locally

This is a Laravel app (PHP 8.3+, Composer, MySQL or SQLite). Full step-by-step guide, demo logins and troubleshooting: **[PROGRESS.md → How to run the system](PROGRESS.md)**.

```
composer setup              # first time only: packages, keys, tables, demo data (SQLite, no edits needed)
php artisan app:start       # every time: web server + queue worker + scheduler
```

Then open `http://127.0.0.1:8000/` for the applicant portal and `http://127.0.0.1:8000/admin` for the admission office. Bootstrap, jQuery, and the fonts load from CDNs, so the browser needs an internet connection for those assets.

## Project structure

```
routes/web.php                 Routes: / (applicant portal), /admin (admission office)
resources/views/
  layouts/app.blade.php        Shared page shell: head, fonts, Bootstrap, jQuery, core.css/core.js
  applicant.blade.php          Applicant portal
  admin/index.blade.php        Admission office panel
public/assets/
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

Load order matters: the layout loads `core.js` first, then each page pushes its own scripts onto the `scripts` stack. In the admission office, views register on `ADM.views` and `admin.js` boots last.

## Backend

Both sides now run on a Laravel JSON API (`routes/api.php`) with JWT sign-in, roles (applicant, admission, clinic, sysadmin), MySQL, an append-only audit log, in-app and email notifications, and private document storage. Still browser-only, on hold until the team settles the mapping: the applicant portal's document cards and its "Submit" button.

Progress, decisions, open questions and performance numbers: [PROGRESS.md](PROGRESS.md). Screen-by-screen wiring: [docs/UI_BACKEND_MATRIX.md](docs/UI_BACKEND_MATRIX.md).
