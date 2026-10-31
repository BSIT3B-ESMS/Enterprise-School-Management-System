<?php

use App\Models\{LeaveBalance, Payslip};
use App\Services\{EmsService, PayrollService};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

function emsBalanceFixture(string $employeeNo, string $days = '7.50', string $asOf = '2026-10-30', string $updatedAt = '2026-10-30T06:00:00Z'): array
{
    return [
        'employee_no' => $employeeNo,
        'leave_type' => 'vacation_leave',
        'remaining_days' => $days,
        'as_of' => $asOf,
        'updated_at' => $updatedAt,
    ];
}

it('treats leave credits as EMS-owned read-only facts and ignores older snapshots', function () {
    $employee = employeeFixture();
    $service = app(EmsService::class);
    $row = emsBalanceFixture($employee->employee_no);
    $service->importBalances([$row]);
    $balance = LeaveBalance::firstOrFail();
    expect($balance->remaining_days)->toBe('7.50');

    // An older "as of" date cannot replace the current balance even when
    // that historical data is corrected later by EMS.
    $service->importBalances([emsBalanceFixture(
        $employee->employee_no, '12.00', '2026-10-20', '2026-10-31T06:00:00Z'
    )]);
    expect($balance->fresh()->remaining_days)->toBe('7.50')
        ->and($balance->fresh()->as_of->toDateString())->toBe('2026-10-30');

    // A replay with the exact same source timestamp is idempotent.
    $service->importBalances([$row]);
    expect(LeaveBalance::count())->toBe(1);

    // Contradictory values with the same EMS timestamp must not silently win.
    expect(fn () => $service->importBalances([array_replace($row, ['remaining_days' => '6.00'])]))
        ->toThrow(ValidationException::class);
    expect($balance->fresh()->remaining_days)->toBe('7.50');

    // A newer EMS snapshot does replace the mirror; PMS itself does not debit it.
    $service->importBalances([emsBalanceFixture(
        $employee->employee_no, '6.50', '2026-10-31', '2026-10-31T06:00:00Z'
    )]);
    expect($balance->fresh()->remaining_days)->toBe('6.50');
});

it('prevents payslip release with a stale or absent EMS leave credit feed in live mode', function () {
    $employee = employeeFixture();
    policyFixture();
    completedAttendance($employee);
    $service = app(PayrollService::class);
    $period = $service->period('2026-10-01');
    $service->compute($period, null);
    $slip = Payslip::firstOrFail();
    config(['ems.enabled' => true]);

    // Employee + approval sync alone are insufficient if EMS is also the
    // source of official remaining leave credits.
    foreach (['employees', 'leaves'] as $resource) {
        DB::table('ems_sync_runs')->insert([
            'resource' => $resource, 'status' => 'success', 'record_count' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
    expect(fn () => $service->release($slip, true, null))->toThrow(ValidationException::class);
    expect($slip->fresh()->status)->toBe('draft');

    DB::table('ems_sync_runs')->insert([
        'resource' => 'balances', 'status' => 'success', 'record_count' => 1,
        'created_at' => now()->subHours(25), 'updated_at' => now()->subHours(25),
    ]);
    expect(fn () => $service->release($slip, true, null))->toThrow(ValidationException::class);

    DB::table('ems_sync_runs')->where('resource', 'balances')->update([
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $service->release($slip, true, null);
    expect($slip->fresh()->status)->toBe('released');
});
