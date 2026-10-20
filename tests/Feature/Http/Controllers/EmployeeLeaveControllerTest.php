<?php

use App\Enums\AuditAction;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\Sex;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveBalances;
use Database\Seeders\LeavePolicySeeder;

/**
 * @return array{0: User, 1: Employee}
 */
function employeeWithLogin(array $employeeState = []): array
{
    $employee = Employee::factory()->create(['hired_on' => '2025-03-02', ...$employeeState]);

    return [User::factory()->create(['employee_id' => $employee->id]), $employee];
}

function leaveInput(array $overrides = []): array
{
    return ['leave_type' => 'vacation', 'start_date' => '2026-03-09', 'end_date' => '2026-03-11', 'reason' => 'Family matters', ...$overrides];
}

beforeEach(function () {
    $this->seed(LeavePolicySeeder::class);
    $this->travelTo(now()->setDate(2026, 3, 2)->setTime(9, 0));
});

describe('the page', function () {
    it('shows the credits for the year with used, pending, and left', function () {
        [$user, $employee] = employeeWithLogin();
        LeaveRequest::factory()->approved()->create(['employee_id' => $employee->id, 'start_date' => '2026-02-10', 'end_date' => '2026-02-12', 'days' => 3]);
        LeaveRequest::factory()->create(['employee_id' => $employee->id, 'start_date' => '2026-03-16', 'end_date' => '2026-03-17', 'days' => 2]);

        $this->actingAs($user)->get(route('employee.leave'))
            ->assertOk()
            ->assertSee('Leave credits')
            ->assertSeeInOrder(['Vacation Leave', '15', '3', '2', '10'], escape: false);
    });

    it('lists the requests with HR\'s note on a rejection', function () {
        [$user, $employee] = employeeWithLogin();
        LeaveRequest::factory()->create(['employee_id' => $employee->id, 'status' => LeaveStatus::Rejected, 'decision_note' => 'Too many staff are away']);

        $this->actingAs($user)->get(route('employee.leave'))->assertSee('Rejected')->assertSee('HR: Too many staff are away');
    });

    it('gives part-time staff vacation and sick credits in proportion to their weekly hours', function () {
        [$user] = employeeWithLogin(['employment_type' => 'part_time', 'weekly_hours' => 20]);

        $this->actingAs($user)->get(route('employee.leave'))
            ->assertSee('Vacation Leave')
            ->assertSee('7.5')
            ->assertSee('50% of the full-time')
            ->assertDontSee('Special Privilege Leave');
    });

    it('earns vacation and sick leave each month from the hire date, 1.25 days a month, and carries it over', function () {
        [$user] = employeeWithLogin(['hired_on' => '2021-03-02']);

        $this->actingAs($user)->get(route('employee.leave'))->assertSeeInOrder(['Vacation Leave', '75']);
        expect((new LeaveBalances)->earned($user->employee, 15))->toBe(75.0);
    });

    it('earns nothing before the hire date', function () {
        [$user] = employeeWithLogin(['hired_on' => '2026-06-01']);

        expect((new LeaveBalances)->earned($user->employee, 15))->toBe(0.0);
    });

    it('offers only the leave types the law makes available to the employee', function () {
        [$user] = employeeWithLogin(['sex' => Sex::Male, 'marital_status' => 'Single']);

        $this->actingAs($user)->get(route('employee.leave'))
            ->assertSee('Vacation Leave')
            ->assertDontSee('Maternity Leave')
            ->assertDontSee('Paternity Leave');
    });

    it('explains when the account is not linked to an employee record', function () {
        $this->actingAs(unlinkedEmployeeUser())->get(route('employee.leave'))->assertOk()->assertSee('not linked to an employee record');
    });
});

describe('filing a request', function () {
    it('saves a pending request with the weekdays it uses, its history, and an audit entry', function () {
        [$user, $employee] = employeeWithLogin();

        $this->actingAs($user)->post(route('employee.leave.store'), leaveInput())->assertRedirect(route('employee.leave'));

        $request = LeaveRequest::query()->sole();

        expect($request->employee_id)->toBe($employee->id)
            ->and($request->status)->toBe(LeaveStatus::Pending)
            ->and((float) $request->days)->toBe(3.0)
            ->and($request->reason)->toBe('Family matters')
            ->and($request->statusChanges()->sole()->to_status)->toBe(LeaveStatus::Pending)
            ->and(AuditLog::query()->where('action', AuditAction::Create)->where('entity_id', $request->id)->where('user_id', $user->id)->exists())->toBeTrue();
    });

    it('does not count weekends against a weekday leave', function () {
        [$user] = employeeWithLogin();

        $this->actingAs($user)->post(route('employee.leave.store'), leaveInput(['start_date' => '2026-03-06', 'end_date' => '2026-03-09']));

        expect((float) LeaveRequest::query()->sole()->days)->toBe(2.0);
    });

    it('counts calendar days for maternity leave', function () {
        [$user] = employeeWithLogin(['sex' => Sex::Female]);

        $this->actingAs($user)->post(route('employee.leave.store'), leaveInput(['leave_type' => 'maternity', 'start_date' => '2026-03-09', 'end_date' => '2026-06-21']))
            ->assertSessionHasNoErrors();

        expect((float) LeaveRequest::query()->sole()->days)->toBe(105.0);
    });

    it('lets sick leave start up to 30 days ago', function () {
        [$user] = employeeWithLogin();

        $this->actingAs($user)->post(route('employee.leave.store'), leaveInput(['leave_type' => 'sick', 'start_date' => '2026-02-20', 'end_date' => '2026-02-20']))
            ->assertSessionHasNoErrors();
    });

    it('rejects :scenario and saves nothing', function (array $override, string $field, array $employeeState = []) {
        [$user] = employeeWithLogin($employeeState);

        $this->actingAs($user)->post(route('employee.leave.store'), leaveInput($override))->assertSessionHasErrors($field);

        expect(LeaveRequest::query()->count())->toBe(0);
    })->with([
        'a vacation that starts in the past' => [['start_date' => '2026-02-27', 'end_date' => '2026-03-03'], 'start_date'],
        'a sick leave from more than 30 days ago' => [['leave_type' => 'sick', 'start_date' => '2026-01-10', 'end_date' => '2026-01-12'], 'start_date'],
        'a last day before the first day' => [['start_date' => '2026-03-11', 'end_date' => '2026-03-09'], 'end_date'],
        'a yearly leave that crosses into a new year' => [['leave_type' => 'special_privilege', 'start_date' => '2026-12-30', 'end_date' => '2027-01-05'], 'end_date'],
        'dates that are all weekend' => [['start_date' => '2026-03-14', 'end_date' => '2026-03-15'], 'start_date'],
        'more days than are left' => [['start_date' => '2026-03-09', 'end_date' => '2026-03-30'], 'end_date'],
        'an unknown leave type' => [['leave_type' => 'holiday'], 'leave_type'],
        'maternity leave for a man' => [['leave_type' => 'maternity'], 'leave_type', ['sex' => Sex::Male]],
        'special privilege leave for part-time staff' => [['leave_type' => 'special_privilege'], 'leave_type', ['employment_type' => 'part_time', 'weekly_hours' => 20]],
        'a missing date' => [['start_date' => ''], 'start_date'],
    ]);

    it('rejects dates that overlap leave already pending or approved', function () {
        [$user, $employee] = employeeWithLogin();
        LeaveRequest::factory()->create(['employee_id' => $employee->id, 'start_date' => '2026-03-10', 'end_date' => '2026-03-12', 'days' => 3]);

        $this->actingAs($user)->post(route('employee.leave.store'), leaveInput(['start_date' => '2026-03-12', 'end_date' => '2026-03-13']))
            ->assertSessionHasErrors('start_date');
    });

    it('does not count cancelled or rejected requests as overlapping or as using credits', function () {
        [$user, $employee] = employeeWithLogin();
        LeaveRequest::factory()->create(['employee_id' => $employee->id, 'start_date' => '2026-03-09', 'end_date' => '2026-03-20', 'days' => 10, 'status' => LeaveStatus::Cancelled]);
        LeaveRequest::factory()->create(['employee_id' => $employee->id, 'start_date' => '2026-03-09', 'end_date' => '2026-03-20', 'days' => 10, 'status' => LeaveStatus::Rejected]);

        $this->actingAs($user)->post(route('employee.leave.store'), leaveInput(['start_date' => '2026-03-09', 'end_date' => '2026-03-20']))
            ->assertSessionHasNoErrors();
    });

    it('counts approved and pending days against what is left', function () {
        [$user, $employee] = employeeWithLogin();
        LeaveRequest::factory()->approved()->create(['employee_id' => $employee->id, 'start_date' => '2026-02-02', 'end_date' => '2026-02-13', 'days' => 10]);
        LeaveRequest::factory()->create(['employee_id' => $employee->id, 'start_date' => '2026-02-16', 'end_date' => '2026-02-19', 'days' => 4]);

        $this->actingAs($user)->post(route('employee.leave.store'), leaveInput(['start_date' => '2026-03-09', 'end_date' => '2026-03-10']))
            ->assertSessionHasErrors('end_date');
    });

    it('refuses an employee who is archived', function () {
        [$user] = employeeWithLogin(['status' => 'resigned']);

        $this->actingAs($user)->post(route('employee.leave.store'), leaveInput())->assertSessionHasErrors('leave_type');
    });

    it('is forbidden to an account with no employee record', function () {
        $this->actingAs(unlinkedEmployeeUser())->post(route('employee.leave.store'), leaveInput())->assertForbidden();
    });
});

describe('cancelling a request', function () {
    it('withdraws a pending request and keeps it in the history', function () {
        [$user, $employee] = employeeWithLogin();
        $request = LeaveRequest::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($user)->post(route('employee.leave.cancel', $request))->assertRedirect(route('employee.leave'));

        expect($request->fresh()->status)->toBe(LeaveStatus::Cancelled)
            ->and($request->statusChanges()->latest('id')->first()->to_status)->toBe(LeaveStatus::Cancelled);
    });

    it('cannot cancel a request HR has already decided', function () {
        [$user, $employee] = employeeWithLogin();
        $request = LeaveRequest::factory()->approved()->create(['employee_id' => $employee->id]);

        $this->actingAs($user)->post(route('employee.leave.cancel', $request))->assertSessionHas('error');

        expect($request->fresh()->status)->toBe(LeaveStatus::Approved);
    });

    it('cannot cancel someone else\'s request', function () {
        [$user] = employeeWithLogin();
        $request = LeaveRequest::factory()->create();

        $this->actingAs($user)->post(route('employee.leave.cancel', $request))->assertForbidden();

        expect($request->fresh()->status)->toBe(LeaveStatus::Pending);
    });
});

it('keeps each leave type\'s credits separate', function () {
    [$user, $employee] = employeeWithLogin();
    LeaveRequest::factory()->approved()->create(['employee_id' => $employee->id, 'leave_type' => LeaveType::Sick, 'start_date' => '2026-02-02', 'end_date' => '2026-02-13', 'days' => 10]);

    $this->actingAs($user)->post(route('employee.leave.store'), leaveInput(['start_date' => '2026-03-09', 'end_date' => '2026-03-20']))
        ->assertSessionHasNoErrors();
});
