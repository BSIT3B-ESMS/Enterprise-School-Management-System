<?php

use App\Models\{Employee, LeaveBalance, WorkflowRequest};
use App\Services\EmsService;
use Illuminate\Support\Facades\{Http, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

function emsLeaveContract(): void
{
    config([
        'ems.enabled' => true,
        'ems.base_url' => 'https://ems.example.test',
        'ems.token' => 'secret-application-token',
        'ems.leave_requests_path' => '/api/v1/leave-requests',
        'ems.leave_request_fields' => [
            'request_id' => 'request_id', 'employee_no' => 'employee_no',
            'starts_on' => 'starts_on', 'ends_on' => 'ends_on',
            'leave_type' => 'leave_type', 'reason' => 'reason',
        ],
        'ems.leave_proof_field' => null,
    ]);
}

function leaveRequestFixture(?string $proof = null): WorkflowRequest
{
    $employee = employeeFixture();
    return WorkflowRequest::create([
        'request_id' => (string) Str::uuid(), 'employee_id' => $employee->id,
        'type' => 'leave', 'work_date' => '2026-11-02',
        'reason' => 'Medical leave for appointment',
        'payload' => ['ends_on' => '2026-11-03', 'leave_type' => 'sick_leave'],
        'attachment_path' => $proof, 'attachment_name' => $proof ? 'medical-proof.pdf' : null,
        'status' => 'pending_ems',
    ]);
}

it('refuses to silently discard leave proof when EMS has not agreed the multipart contract', function () {
    Storage::fake('local');
    Storage::disk('local')->put('request-attachments/proof.pdf', '%PDF-1.4 example');
    emsLeaveContract();
    Http::fake();
    $request = leaveRequestFixture('request-attachments/proof.pdf');
    expect(fn () => app(EmsService::class)->sendLeave($request))->toThrow(ValidationException::class);
    Http::assertNothingSent();
    expect($request->fresh()->status)->toBe('pending_ems');
});

it('sends leave proof as multipart with a stable UUID idempotency key', function () {
    Storage::fake('local');
    Storage::disk('local')->put('request-attachments/proof.pdf', '%PDF-1.4 example');
    emsLeaveContract();
    config(['ems.leave_proof_field' => 'proof']);
    $request = leaveRequestFixture('request-attachments/proof.pdf');
    Http::fake(['*' => Http::response(['data' => [
        'request_id' => $request->request_id, 'employee_no' => $request->employee->employee_no,
    ], 'meta' => (object) []], 201)]);
    app(EmsService::class)->sendLeave($request);
    Http::assertSent(fn ($sent) => $sent->hasHeader('Authorization', 'Bearer secret-application-token')
        && $sent->hasHeader('Idempotency-Key', $request->request_id)
        && str_contains((string) ($sent->header('Content-Type')[0] ?? ''), 'multipart/form-data'));
    expect($request->fresh()->status)->toBe('pending_ems'); // Only EMS approves.
});

it('rejects a malformed or mismatched EMS leave acknowledgement without approval', function () {
    emsLeaveContract();
    $request = leaveRequestFixture();
    Http::fake(['*' => Http::response(['status' => 'success', 'id' => 1], 201)]);
    expect(fn () => app(EmsService::class)->sendLeave($request))->toThrow(ValidationException::class);
    expect($request->fresh()->status)->toBe('pending_ems');
    Http::fake(['*' => Http::response(['data' => ['request_id' => (string) Str::uuid()]], 201)]);
    expect(fn () => app(EmsService::class)->sendLeave($request))->toThrow(ValidationException::class);
});

it('preserves newer EMS employee facts when stale payloads arrive out of order', function () {
    $row = [
        'employee_no' => '26-00032', 'full_name' => 'Employee', 'monthly_rate' => '22500.00',
        'status' => 'active', 'salary_effective_on' => '2026-10-01',
        'updated_at' => '2026-10-30T06:00:00Z',
    ];
    app(EmsService::class)->importEmployees([$row]);
    app(EmsService::class)->importEmployees([array_replace($row, [
        'monthly_rate' => '10000.00', 'status' => 'terminated',
        'updated_at' => '2026-10-20T06:00:00Z',
    ])]);
    $employee = Employee::where('employee_no', $row['employee_no'])->firstOrFail();
    expect($employee->monthly_rate)->toBe('22500.00')->and($employee->status)->toBe('active');
});

it('mirrors official EMS leave balances without inventing or subtracting credits', function () {
    $employee = employeeFixture();
    $row = [
        'employee_no' => $employee->employee_no, 'leave_type' => 'vacation_leave',
        'remaining_days' => '7.50', 'as_of' => '2026-10-30',
        'updated_at' => '2026-10-30T01:00:00Z',
    ];
    app(EmsService::class)->importBalances([$row]);
    $balance = LeaveBalance::firstOrFail();
    expect($balance->remaining_days)->toBe('7.50');
    $row['remaining_days'] = '99.00';
    $row['updated_at'] = '2026-10-25T01:00:00Z';
    app(EmsService::class)->importBalances([$row]);
    expect($balance->fresh()->remaining_days)->toBe('7.50');
    $row['remaining_days'] = 4.5;
    expect(fn () => app(EmsService::class)->importBalances([$row]))->toThrow(ValidationException::class);
});

it('does not accept EMS pagination with non-null final cursor', function () {
    config([
        'ems.enabled' => true, 'ems.base_url' => 'https://ems.example.test',
        'ems.token' => 'private-token', 'ems.employees_path' => '/api/v1/employees',
        'ems.employee_fields' => ['employee_no' => 'employee_no'],
    ]);
    Http::fake(['*' => Http::response([
        'data' => [], 'meta' => ['next_cursor' => 'unexpected', 'has_more' => false],
    ], 200)]);
    expect(fn () => app(EmsService::class)->sync('employees'))->toThrow(ValidationException::class);
    $this->assertDatabaseHas('ems_sync_runs', ['status' => 'failed']);
});
