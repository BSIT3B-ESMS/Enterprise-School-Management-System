# TAP-PMS (UI)
Time, Attendance and Payroll Management System, TAP.
UI built with **HTML + jQuery + Bootstrap 5** (as required by the instructor). jQuery is loaded from `vendor/jquery.min.js` and used for document-ready, event binding, value reads and class toggling in `js/app.js`. Theme: `css/tap-theme.css` (TAP). Works offline, no install needed.

## How to run
Open `index.html` in Chrome or Edge. That's it.

Demo accounts (password: `password`)
| Role | Employee No. |
|---|---|
| Employee | EMP-001 (password: emp123) |
| HR | HR-001 (Hannah Reyes) |
| Admin | ADM-001 |

"Reset demo" (top right) brings back the sample data.

## What is already in the UI
- Login with Employee No., roles: Employee, HR, Admin
- Employee: time in/out (RFID simulate button), manual attendance entry (needs HR approval), leave with leave type + dates + auto day count, overtime request, schedule change request (e.g. WFH, Mon & Tue only), leave balance, payslips (print)
- HR: dashboard, approvals (leave, overtime, schedule change, manual attendance), attendance list, employees, payroll (run, release), reports (CSV, print), API reference
- Admin: employees, positions with salary range and fixed rate, users and roles
- Approving leave deducts the days from the leave balance automatically
- Payroll deductions (SSS, PhilHealth, Pag-IBIG, tax, loans) appear automatically
- Overtime is paid only when HR approves it
- Salary rate: "Use fixed rate" button fills in the position's fixed rate, but it stays editable inside the allowed range

## API-ready
Every screen calls the system only through `API.request(method, url, body)` in `js/api.js`, using `/api/v1/...` endpoints (see the "API Reference" page inside the app).
Right now a mock API answers (data saved in the browser). To use the real backend, edit `js/api.js`:

    CONFIG.USE_REMOTE = true;
    CONFIG.BASE_URL  = 'http://localhost:3000/api/v1';

No page code needs to change. The real backend should return `{ "status": "success", "data": ... }` or `{ "message": "..." }` on errors.

## Files
- `index.html` : login + app shell
- `css/style.css` : theme
- `js/store.js` : sample data + payroll computation (demo only)
- `js/api.js` : API layer (mock or real)
- `js/app.js` : all pages (jQuery)
- `vendor/` : jQuery, Bootstrap, Bootstrap Icons, Chart.js, Inter font (local copies)

Payroll rules in the demo are simplified: daily rate = monthly / 22, SSS 5%, PhilHealth 2.5%, Pag-IBIG 2% (max P200), withholding tax by the TRAIN table.

## Leave attachments
Employee leave files (JPG, PNG, PDF, max 5 MB) are saved by `upload_leave.php` into `proofs/` as `LEAVE_<control no>.<ext>`. Needs PHP and a writable `proofs/` folder, same as the kiosk photos. Admin opens them from Appraisals & Leaves, File column.
