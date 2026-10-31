# Login ID Correction — TAP-PMS Original UI

The original v4 TAP-PMS uses **Employee ID + Password**, **not mandatory email**. The official API document addresses application-to-application Bearer authentication separately and assigns ownership of `employee_no` to EMS.

## New first-time setup

Open `/setup` only on a new installation. Enter a name, an **Admin ID** such as `ADMIN-001`, and a 12+ character password. No email is requested. Set up your authenticator code as before. Sign in at `/login` using that Admin ID.

## Employees

Import/sync EMS employee records, then an admin opens the employee in Workforce and creates an account with a temporary password. The **login ID is always the exact EMS `employee_no`** (e.g. `26-00001`). Email is not required for the account and is not used to generate the employee number. No separate official employee ID is invented by TAP-PMS.

## Updating an already-running TAP-PMS Integrated instance

**Back up your MySQL `tap_pms` database and `.env` first.** Close the running Laravel server. Copy the updated project files (or the contents of the small patch ZIP) into your current project, preserving your existing `.env`, private storage and other application data. Use the **same PHP 8.4 terminal** from earlier and run:

```powershell
php artisan config:clear
php artisan migrate
php artisan view:clear
php artisan serve
```

Do **not** run `migrate:fresh`, change the existing `APP_KEY`, or re-run `composer install` if the current `vendor` folder is already installed and correct. The new migration adds nullable `login_id`, makes email optional, and backfills IDs for existing accounts without wiping password hashes.

**Existing admin accounts:** the migration assigns the ID `ADMIN-00001` for user database ID 1 (or `ADMIN-00002` for ID 2, and so on). For legacy accounts, the old email continues to work as a **temporary compatibility fallback** with the existing password, in case you do not know the admin ID yet. Once signed in, the new Admin ID is the preferred credential. Admin MFA is unchanged.

**Existing employee accounts:** the migration links login IDs to the employee's official EMS `employee_no`. The original password remains valid; inactive/retired employees are still denied.

## What this changes (and does NOT change)

This patch changes the human login, first-admin setup, account creation and the associated tests. It **does not** change app-to-app API Bearer tokens, the `/api/v1` routes, integration permissions, EMS ownership of employee records, payroll calculations, database server requirements, or live EMS availability. The final system still requires full local integration and browser testing.
