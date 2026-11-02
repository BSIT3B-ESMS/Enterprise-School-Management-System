Current stack update: the user restored MariaDB/MySQL on October 31, 2026. The active target is configured privately in .env. SQLite is retained only as an archived rollback source/compatibility utility. See LOCAL-SETUP.md for transfer verification and backup operations. Earlier SQLite-only statements below are historical.

# SMTP and institutional integration handoff

The Laravel app supports authenticated Admission/EMS push endpoints and approved JSON file exchange. File exchange is a supported integration, not a missing background-polling service. Actual payloads, credential ownership and institutional connectivity still need confirmation with those teams.

## SMTP

Set private environment values: MAIL_MAILER=smtp, MAIL_SCHEME=smtps for implicit TLS where the provider requires it, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS and MAIL_FROM_NAME. Use the provider's documented TLS/STARTTLS configuration. Never put SMTP passwords in the settings page, screenshots or Git. Reload application configuration after changes. Request a reset for an authorized test account and verify receipt, link host, expiry and single use before enabling institutional account invitations. The current log mailer captures local messages; it does not deliver to inboxes. No SMTP credentials were found in the supplied source project.

## Integration acceptance

Use docs/API_DOCUMENTATION.md for the existing v1 contracts. Agree source/schema_version, stable identifiers, curriculum mapping, employment status and teaching assignments with each system owner. Use synthetic data in connectivity checks. Create a dedicated active Registrar service account, then issue an import-only token with `php artisan enrollment:integration-token admission --user=<id>` or `ems`. The token is displayed once and expires in 30 days. Deliver it through the approved credential channel, never email it or commit it. These tokens cannot access general portal endpoints. Transfer over HTTPS in production, or use approved structured exports.

Verify create/update/reject counts, duplicate prevention, allowed update fields, inactive EMS staff, unknown subject codes and sanitized errors. Review real academic year and curriculum mappings rather than assuming the supplied historical catalog is current. Record acceptance with the institution's owners; an automated local test is not proof of live integration.

## GitHub and hosting

The supplied reference folder has origin https://github.com/rnmdn/EnrollmentSystem.git and a separate Node/SQLite application. The user selected reusing its contents in Laravel. Publishing Laravel over that repository's main branch would replace a different application, so it has not been done. Supply a destination repository for this Laravel project or explicitly choose a reviewed subdirectory/branch plan. No real database, .env, backup, secret or screenshot belongs in the source repository.

No hosting destination or production credentials were supplied. Follow docs/DEPLOYMENT.md and run `php artisan enrollment:readiness` on the intended host. A deployable configuration requires HTTPS, debug off, demonstration off, a protected key, verified SMTP, the approved grading policy, a service owner, backup retention and a school acceptance pass. Verify actual integration and mail delivery separately from the configuration check.

Current database platform: SQLite only, by user instruction. No MySQL/RDS endpoint or database credentials are required for the app. Keep the live database on a protected local filesystem on the selected single host. The SDD's proposed MariaDB/RDS platform is now an architecture deviation requiring documentation. Existing Admission/EMS HTTP contracts and role controls remain unchanged.
