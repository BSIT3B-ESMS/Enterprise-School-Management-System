# Upgrade from v7

1. Back up your existing MySQL database and private storage. Extract v8 into a separate folder.
2. Copy the existing `.env` and private evidence storage into the new project. Keep the exact existing `APP_KEY`: do not run `key:generate` on an existing database. A new key cannot decrypt previously encrypted MFA secrets.
3. In the folder containing artisan, run `composer install`, `php artisan config:clear`, `php artisan migrate`, and `php artisan view:clear`.
4. Review Settings and select automatic official deductions. In Workforce confirm each employee’s effective SSS, GSIS or no-pension coverage, PhilHealth, Pag-IBIG and withholding applicability with HR. No scheme is inferred from the institution name. Enter confirmed employee overrides/recurring loans where applicable; blank means automatic, zero explicitly means zero.
5. Recompute draft payroll after coverage confirmation. Released records remain historical snapshots; they are never silently rewritten. Reconcile a trial cut-off before releasing real pay.
6. Start with `php artisan serve`, then visit `/login`. Keep MySQL running.

If a local test administrator has an unreadable MFA secret after losing the old key, stop the server and run `php artisan pms:reset-mfa YOUR_ADMIN_LOGIN_ID --confirm` in local mode, then restart and enroll the authenticator again. This explicitly clears only that administrator’s MFA enrollment/recovery values. It does not recover other data encrypted by the lost key. Never reset production MFA merely to bypass authentication.

The new migrations add deduction overrides, contribution coverage profiles and observed EMS employment history. Existing employees, attendance, payroll and evidence are retained. Live EMS still requires the agreed upstream endpoints and acceptance testing; see docs/EMS-TEAM-REQUIREMENTS.md.
