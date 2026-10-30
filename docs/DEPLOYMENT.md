# Institutional configuration and deployment

The verified deliverable is a local development implementation with synthetic data. Before real use, an authorized school representative must review curriculum, grading, credit equivalence, override authority, program assignments, room inventory, COR template and enrollment acceptance rules.

## Configuration

- Use a dedicated production MariaDB database and account with only the necessary database privileges. The portable local tools and demonstration database are not a production deployment.
- Provision approved real records through the agreed, versioned Admission/EMS contracts. Use dedicated import-only bearer tokens. Exchange credentials securely and rotate/revoke them when no longer required.
- Supply the actual curriculum versions, official student numbering policy, authorized grade data and professor availability. Set GRADE_ORDER only after the scale is confirmed.
- Replace the current COR presentation with the approved institutional form/logo if different. Verify generated PDFs with realistic names and full semester loads.
- Configure an approved SMTP service, confirm reset/invitation delivery and token expiry, and protect mail credentials in the production environment.

## Hosting

Install PHP compatible with the locked packages and required extensions, Composer dependencies without development packages, and MariaDB. Point the web server document root to public. Configure HTTPS, APP_ENV=production, APP_DEBUG=false, the correct APP_URL, secure session cookies, trusted proxies as applicable, and a private application key. Never regenerate an existing production key casually.

Use standard Laravel deployment commands for configuration, routes, views, migrations and caches under the authorized deployment process. The Windows cache helper is for the current local environment; regular Laravel deployment tools should be used on the target server. Keep writable storage and bootstrap caches outside public access. Do not copy .env, demo credentials, development mail logs or test databases into a public source repository.

## Operations

Take an encrypted database backup before migration and maintain scheduled backups according to the school's retention policy. Include application configuration/keys in a protected recovery plan. Perform a restore rehearsal against an isolated environment. Monitor import failures, failed mail delivery, application exceptions and capacity/schedule validation issues. Retain approved audit and COR history according to school policy.

Run institutional user acceptance testing for regular, irregular, transferee and returning students using approved test data. Test actual Admission/EMS payloads and source availability failures, real SMTP, production browser/device behavior, realistic timetable/semester sizes and expected concurrent enrollment load. The automated local suite is evidence of implemented behavior, not a replacement for those institutional checks.

A GitHub repository, remote access, deployment host, official data and production credentials were not supplied. No external publishing was performed.