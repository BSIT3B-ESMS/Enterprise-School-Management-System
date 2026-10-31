# Original v4 workflow comparison

Reference: original `tap-pms-laravel-v4.zip`, compared with this release. `style.css` and `tap-theme.css` are byte-for-byte identical to v4. The navy/brass theme, animated clock login, masthead, horizontal five-tab navigation and automatic card/photo kiosk presentation are retained. Dynamic data is now rendered by Blade with MySQL, not old localStorage code.

| Original feature | Delivered behavior |
|---|---|
| Admin / employee login and separation | Employee-ID login, server roles, hashed passwords, administrator MFA |
| Admin Overview / Workforce / Approvals / Payroll / Appraisals & Leaves | Original five module tabs retained, real server data |
| Employee Attendance / Requests / Leave / Payslips / Profile | Original five module tabs retained |
| Individual schedules and schedule history | PMS administrator assigns dates, workdays, hours, lunch and grace; employees see hours/history in Profile |
| Automatic AM IN / AM OUT / PM IN / PM OUT | Server infers event from assigned schedule and prior punches; authorized kiosk requires private photo |
| PM-only attendance | No fabricated AM session; late PM arrivals remain valid and unworked minutes are deducted |
| RFID registration | Admin assigns normalized unique card UID |
| Attendance proof and corrections | Owner/admin-only evidence; approved corrections carry an audit trail |
| Overtime, schedule, loan/tax/deduction requests | Employee files, administrator reviews; only approved payroll inputs count |
| Leave filing and proof | PMS files, administrator forwards to EMS, EMS decides; proof forwarding follows agreed multipart contract |
| Leave credits | Read-only EMS mirror; missing balance is unavailable, not zero |
| Appraisal / salary history | EMS effective salary history displayed; PMS does not edit EMS master salary |
| Semi-monthly payroll | 1–15 / 16–month-end; assigned schedules and effective salary; integer centavo arithmetic |
| Individual payslip release | Per employee/cut-off, frozen snapshot, withdrawal before recomputation |
| Payslip and reports | Real PDF, controlled report snapshots, signatory and CSV export |
| Search / filters | Server-rendered search for workforce/attendance/approvals/payroll and cut-off filters |

| Automatic statutory contributions | Dated official SSS/GSIS, PhilHealth and Pag-IBIG employee shares; confirmed employee coverage required |
| Automatic tax | BIR semi-monthly withholding on actual taxable cut-off pay |
| Employee deduction/loan overrides | Effective-dated, reasoned per-cut-off amounts; blank auto / explicit zero supported |
| Bulk release | All-or-nothing release using the same checks as individual release |
| DTR status, grace and correction | Computed status and metrics; grace credited separately from actual work; corrections fill missing punches without replacing kiosk evidence |
| Employment history | Observed EMS field changes with old/new values; unchanged syncs do not invent history |
| Advanced filters and reports | Workforce card/status, request type/date/status, attendance status, payroll attendance/release status; filtered controlled exports |
| Report signatures | Prepared/reviewed/approved fields and payslip receipt line |

Prototype demo passwords, client role switching, destructive browser reset and direct PMS edits of EMS master data are excluded. Historical salary changes come from EMS. PMS files leave and EMS approves it. Original CSS is retained, while server forms and persisted records replace prototype-only interactions; this does not claim every modal is pixel-identical. See STATUTORY-RULES.md for dated rules and limits, and EMS-TEAM-REQUIREMENTS.md for live integration prerequisites.
