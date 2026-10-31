# EMS leave balance ownership — TAP-PMS implementation update

Decision confirmed by the TAP-PMS/EMS teams: **EMS is the authoritative owner of remaining leave balance and leave credits**. This corrects the short-lived proposal to manage leave credits in TAP-PMS. It does not change the original TAP-PMS frontend navigation or kiosk behavior.

## Actual ownership and request flow

1. Employee files leave on the **TAP-PMS File a Leave** page, including leave type, date range, reason, and optional proof. TAP-PMS issues its own request UUID for idempotent submission.
2. TAP-PMS passes the request to the **agreed EMS endpoint** using a server-side Bearer token, `Idempotency-Key`, and the required multipart evidence field if attached. EMS, not TAP-PMS, approves or rejects.
3. EMS returns approval/rejection and whether the leave is paid; TAP-PMS mirrors the decision and uses it for payroll. Pending or rejected leave must never count as approved paid leave.
4. EMS allocates/debits/restores **leave credits** and owns the official remaining balance. TAP-PMS only imports and displays the EMS value as a read-only mirror, without independently calculating or subtracting credits.
5. For **live mode**, payslip release requires a successful employee, leave decision, **and leave balance** sync in the past 24 hours. Failed/missing sync blocks release; it does not replace an unavailable balance with zero.

## Code locations

- `app/Services/EmsService.php`: `sync('balances')` and `importBalances()` validate/replicate EMS balance facts, reject conflicting equal-timestamp updates, and avoid replacing newer as-of dates with older ones. `sendLeave()` handles leave submissions without losing proof.
- `app/Models/LeaveBalance.php` / `database/migrations/2026_10_30_000007_create_ems_leave_balances.php`: `ems_leave_balances` is a **local mirror**, not a TAP-PMS ledger or authority for credits.
- `app/Services/PayrollService.php`: live release checks recent EMS sync for employees, approved leaves, and balances. Payroll computes paid/unpaid status from authoritative EMS decisions; it does not debit credits.
- `resources/views/app/portal.blade.php`: displays official EMS credits as read-only on File a Leave / Appraisals & Leaves; the latter has a separate Refresh EMS leave credits action.
- `routes/console.php`: `php artisan pms:api-contract-check` now requires balance feed path/mapping for live readiness; scheduler syncs balances when configured.
- `tests/Feature/EmsLeaveBalanceOwnershipTest.php`: additional regression tests for credit authority, source-date ordering, replay/conflicts, and payroll release gating.

## Required EMS-side contract (not invented)

The school `API.md` fixes Bearer authentication, `/api/v1`, `data`/`meta`, cursor paging, official `employee_no`, UTC timestamps, and other protocol rules, **but does not name a specific leave-balance endpoint**. Before live integration, EMS must document the **actual endpoint**, `employees:read`-style scoped permission, field names, leave type classifications, available balance unit, authoritative `as_of` date, UTC `updated_at`, and the response format in EMS `docs/openapi.json`.

Expected *canonical PMS mapping*, NOT a claim of an existing EMS path:

```json
{
  "employee_no": "26-00001",
  "leave_type": "vacation_leave",
  "remaining_days": "7.50",
  "as_of": "2026-10-30",
  "updated_at": "2026-10-30T06:00:00Z"
}
```

Do not enable `EMS_ENABLED=true` unless the EMPLOYEES, LEAVES, BALANCES, and LEAVE_REQUESTS paths and field mappings are confirmed. The option `EMS_BALANCES_PATH` is **required for live operation**, not a placeholder to fill with a guessed URL. Keep tokens in `.env` on the server, never in client JavaScript, screenshots, or submitted files. If EMS returns no balance, show **Not synchronized**, not `0.00`.

## Windows upgrade instructions (existing installation)

1. Export/backup `tap_pms` in phpMyAdmin. Also back up the Laravel project, `.env`, and private `storage` directory.
2. Stop the existing `php artisan serve` process. Extract the **patch** and copy the patch's `tap-pms-laravel` contents over the matching folder in your existing **Original Workflow API Updated** build. Do not overwrite `.env` or storage.
3. In the VS Code terminal that already has PHP 8.4 and Composer available, run:

```powershell
php artisan migrate
php artisan optimize:clear
php artisan pms:api-contract-check
php artisan serve
```

4. You do **not** need `composer install` again if `vendor/autoload.php` already exists in this project. Do **not** run `migrate:fresh`, `db:wipe`, or regenerate `APP_KEY` against existing user data.
5. Use `php artisan pms:sync-ems employees`, then `php artisan pms:sync-ems leaves`, then `php artisan pms:sync-ems balances` **only after** the actual EMS contract and token are configured. An EMS sync failure should preserve existing records.
6. Run `php artisan test` **only against a dedicated test database** (Pest uses destructive `RefreshDatabase`); never point it at your real payroll data.

## Verification status

These source changes can be checked with PHP static lint, Blade directive balance, ZIP integrity, and targeted source review. **Live EMS synchronization, Laravel/Pest execution, real MySQL behavior, and end-to-end UI/kiosk acceptance have not been verified here.** This is a safe source update for integration testing, not a claim of live production readiness.
