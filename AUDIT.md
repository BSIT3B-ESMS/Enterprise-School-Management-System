# Version 6 implementation changes

The v5 wrapper still relied on prototype browser workflows. This version replaces those active routes and scripts with server-rendered Blade pages and MySQL services.

Implemented: password authentication; admin TOTP MFA and recovery codes; employee ownership and administrator role checks; CSRF-protected forms; effective schedules and salary history; registered kiosk pairing and private attendance photos; four-punch attendance; request/proof/approval workflows; server payroll calculation; individual cut-off releases and withdrawal; PDF payslips; controlled PDF/CSV report snapshots; audit events; configurable EMS read/submission adapter; app-to-app token, permission, cursor and idempotency contract.

Removed from active application: public browser-state sync/upload endpoints, role selection as authentication, browser-owned payroll results, localStorage as database and exposed proof-file URLs. No automatic migration of old browser JSON or default credentials is provided.

External acceptance still needed: real EMS contract/connectivity, institution-approved payroll rules and statutory adjustments, trusted HTTPS deployment, physical reader/camera testing, operational backup/restore and production capacity. These are not claimed as completed merely because local tests pass.
