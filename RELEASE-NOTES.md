# PMS v8 — 30 October 2026

Restores automatic official employee deductions omitted from v7, with explicit HR-confirmed SSS/GSIS coverage, PhilHealth, Pag-IBIG and actual BIR semi-monthly withholding. Adds effective-dated employee exceptions and recurring loans, itemized snapshots and a backward-compatible API deduction breakdown.

Restores and corrects v4 workflows: atomic bulk release, missing-punch corrections without overwriting kiosk data, grace credit separate from actual working time, workforce/request/attendance/payroll filters, all-cut-off employee payslip search, EMS-observed employment history, filtered report exports and signature sections. Original navy/brass CSS and five-tab navigation are preserved. PMS still owns schedules; EMS owns identity, salary, leave decisions and balances.

Unreadable local MFA enrollment now shows a recovery explanation; an explicit local-only administrator recovery command avoids deleting payroll data or silently disabling MFA.

Validation: 66 Pest tests, 357 assertions; MySQL 8 fresh migrations; headless browser acceptance; PHP/Blade/JavaScript checks. See TESTING.md for measured scope and external limits. Live EMS, physical kiosk hardware, institution rate applicability and production performance require separate acceptance.

Read UPGRADE-FROM-V7.md before updating: preserve .env and APP_KEY. Existing released records are not recalculated. Confirm employee contribution profiles and recompute drafts before release.
