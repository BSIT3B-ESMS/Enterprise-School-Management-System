# Agreed ownership for this build

- EMS: official employee number, name, employment status, effective salary, leave balances/credits and leave approval/rejection/cancellation.
- PMS: employee work schedules and schedule history, RFID registration, actual attendance/photos, attendance corrections, overtime workflow, payroll adjustments, semi-monthly calculations, payslip release, reports and PMS login credentials.
- Employee files leave in PMS. PMS sends request ID, employee number, dates, leave type and reason (and proof when EMS agrees the transfer contract). EMS returns a decision correlated to the PMS request ID, an EMS leave ID, final dates and explicit paid/unpaid treatment. Final approved dates are authoritative for payroll even when they match the original request.
- Leave balances are a read-only EMS mirror in PMS. PMS must never debit or allocate the official leave credits. Missing balances display as unavailable rather than zero.
- PMS administrators assign workdays, work start/end, lunch start/end, grace and effective date. Employees can view the assigned schedule under My Profile. Required daily hours are calculated from that schedule excluding configured lunch. Actual worked hours come from PMS punch records.
- PMS schedules are independent of EMS connectivity. No EMS schedule endpoint or schedule feed is required by this build.
