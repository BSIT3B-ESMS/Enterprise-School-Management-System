<?php

use App\Enums\AuditAction;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\LeaveRequest;
use App\Models\LeaveStatusChange;
use App\Models\User;
use Database\Seeders\LeavePolicySeeder;

describe('the list', function () {
    it('opens on the pending requests, with the count of those waiting', function () {
        LeaveRequest::factory()->create(['employee_id' => Employee::factory()->create(['last_name' => 'Waitingperson'])->id]);
        LeaveRequest::factory()->approved()->create(['employee_id' => Employee::factory()->create(['last_name' => 'Approvedperson'])->id]);

        $this->actingAs(hrStaff())->get(route('leave.index'))
            ->assertOk()
            ->assertSee('1 request is waiting for a decision')
            ->assertSee('Waitingperson')
            ->assertDontSee('Approvedperson');
    });

    it('shows each status on its own tab and everything on All', function () {
        LeaveRequest::factory()->approved()->create(['employee_id' => Employee::factory()->create(['last_name' => 'Approvedperson'])->id]);
        LeaveRequest::factory()->create(['status' => LeaveStatus::Rejected, 'employee_id' => Employee::factory()->create(['last_name' => 'Rejectedperson'])->id, 'decided_at' => now()]);

        $this->actingAs(hrAdmin());

        $this->get(route('leave.index', ['status' => 'approved']))->assertSee('Approvedperson')->assertDontSee('Rejectedperson');
        $this->get(route('leave.index', ['status' => 'rejected']))->assertSee('Rejectedperson')->assertDontSee('Approvedperson');
        $this->get(route('leave.index', ['status' => 'all']))->assertSee('Approvedperson')->assertSee('Rejectedperson');
    });

    it('filters by employee, leave type, and college', function () {
        $college = College::factory()->create();
        $title = JobTitle::factory()->create(['college_id' => $college->id]);
        LeaveRequest::factory()->create(['employee_id' => Employee::factory()->for($title)->create(['last_name' => 'Inthecollege'])->id, 'leave_type' => LeaveType::Sick]);
        LeaveRequest::factory()->create(['employee_id' => Employee::factory()->create(['last_name' => 'Elsewhere'])->id]);

        $this->actingAs(hrAdmin());

        $this->get(route('leave.index', ['q' => 'Inthe']))->assertSee('Inthecollege')->assertDontSee('Elsewhere');
        $this->get(route('leave.index', ['type' => 'sick']))->assertSee('Inthecollege')->assertDontSee('Elsewhere');
        $this->get(route('leave.index', ['college' => $college->id]))->assertSee('Inthecollege')->assertDontSee('Elsewhere');
    });

    it('is closed to employees', function () {
        $this->actingAs(employeeUser())->get(route('leave.index'))->assertForbidden();
    });
});

describe('approving', function () {
    it('marks the request approved, records who decided, and keeps the history and an audit entry', function () {
        $head = hrAdmin();
        $request = LeaveRequest::factory()->create();

        $this->actingAs($head)->post(route('leave.approve', $request))->assertSessionHas('success');

        $fresh = $request->fresh();

        expect($fresh->status)->toBe(LeaveStatus::Approved)
            ->and($fresh->decided_by)->toBe($head->id)
            ->and($fresh->decided_at)->not->toBeNull()
            ->and($fresh->statusChanges()->latest('id')->first()->only(['from_status', 'to_status', 'changed_by']))
            ->toEqual(['from_status' => LeaveStatus::Pending, 'to_status' => LeaveStatus::Approved, 'changed_by' => $head->id])
            ->and(AuditLog::query()->where('action', AuditAction::Update)->where('entity_id', $request->id)->latest('id')->first()->changes['event'])->toBe('leave_approved');
    });

    it('does not let HR Staff approve or reject', function () {
        $request = LeaveRequest::factory()->create();

        $this->actingAs(hrStaff())->post(route('leave.approve', $request))->assertForbidden();
        $this->post(route('leave.reject', $request), ['decision_note' => 'No'])->assertForbidden();

        expect($request->fresh()->status)->toBe(LeaveStatus::Pending);
    });

    it('shows HR Staff the requests without the decision buttons', function () {
        LeaveRequest::factory()->create();

        $this->actingAs(hrStaff())->get(route('leave.index'))->assertOk()->assertSee('Waiting for an HR Admin')->assertDontSee('Reason to reject');
    });

    it('flags a request waiting more than 5 working days', function () {
        LeaveRequest::factory()->create(['created_at' => now()->subDays(10)]);
        LeaveRequest::factory()->create(['created_at' => now()->subDay()]);

        $this->actingAs(hrAdmin())->get(route('leave.index'))->assertSee('1 request has waited more than 5 working days');
    });

    it('makes the employee On Leave while the approved leave covers today', function () {
        $employee = Employee::factory()->create(['last_name' => 'Awayperson']);
        $request = LeaveRequest::factory()->create(['employee_id' => $employee->id, 'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addDay()->toDateString(), 'days' => 3]);

        expect($employee->isOnLeaveToday())->toBeFalse();

        $this->actingAs(hrAdmin())->post(route('leave.approve', $request));

        expect($employee->isOnLeaveToday())->toBeTrue();
        $this->get(route('employees.index'))->assertSee('On leave');
        $this->get(route('employees.show', $employee))->assertSee('On leave until');
    });
});

describe('rejecting', function () {
    it('needs a reason, which is kept with the request and shown to the employee', function () {
        $request = LeaveRequest::factory()->create();

        $this->actingAs(hrAdmin())->post(route('leave.reject', $request), ['decision_note' => 'Too many staff are away'])->assertSessionHas('success');

        $fresh = $request->fresh();

        expect($fresh->status)->toBe(LeaveStatus::Rejected)
            ->and($fresh->decision_note)->toBe('Too many staff are away')
            ->and($fresh->statusChanges()->latest('id')->first()->note)->toBe('Too many staff are away');
    });

    it('refuses a rejection without a reason and leaves the request pending', function (string $note) {
        $request = LeaveRequest::factory()->create();

        $this->actingAs(hrAdmin())->post(route('leave.reject', $request), ['decision_note' => $note])->assertSessionHasErrors('decision_note');

        expect($request->fresh()->status)->toBe(LeaveStatus::Pending);
    })->with(['empty' => '', 'spaces only' => '   ']);

    it('does not make the employee On Leave', function () {
        $employee = Employee::factory()->create();
        $request = LeaveRequest::factory()->create(['employee_id' => $employee->id, 'start_date' => now()->toDateString(), 'end_date' => now()->toDateString(), 'days' => 1]);

        $this->actingAs(hrAdmin())->post(route('leave.reject', $request), ['decision_note' => 'No cover']);

        expect($employee->isOnLeaveToday())->toBeFalse();
    });
});

describe('the rules', function () {
    it('makes a decision final', function () {
        $request = LeaveRequest::factory()->create();

        $this->actingAs(hrAdmin())->post(route('leave.approve', $request));
        $this->post(route('leave.reject', $request), ['decision_note' => 'Changed my mind'])->assertSessionHas('error');
        $this->post(route('leave.approve', $request))->assertSessionHas('error');

        expect($request->fresh()->status)->toBe(LeaveStatus::Approved)
            ->and($request->statusChanges()->count())->toBe(1);
    });

    it('does not let anyone decide their own request', function () {
        $employee = Employee::factory()->create();
        $staff = User::factory()->hrAdmin()->create(['employee_id' => $employee->id]);
        $request = LeaveRequest::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($staff)->post(route('leave.approve', $request))->assertForbidden();
        $this->post(route('leave.reject', $request), ['decision_note' => 'No'])->assertForbidden();

        expect($request->fresh()->status)->toBe(LeaveStatus::Pending);
        $this->get(route('leave.index'))->assertSee('another HR Admin decides it');
    });

    it('lets another HR user decide it', function () {
        $employee = Employee::factory()->create();
        User::factory()->hrAdmin()->create(['employee_id' => $employee->id]);
        $request = LeaveRequest::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs(hrAdmin())->post(route('leave.approve', $request))->assertSessionHas('success');
    });

    it('cannot decide a cancelled request', function () {
        $request = LeaveRequest::factory()->create(['status' => LeaveStatus::Cancelled]);

        $this->actingAs(hrAdmin())->post(route('leave.approve', $request))->assertSessionHas('error');

        expect($request->fresh()->status)->toBe(LeaveStatus::Cancelled);
    });

    it('is closed to employees', function () {
        $request = LeaveRequest::factory()->create();

        $this->actingAs(employeeUser())->post(route('leave.approve', $request))->assertForbidden();
        $this->post(route('leave.reject', $request), ['decision_note' => 'x'])->assertForbidden();

        expect($request->fresh()->status)->toBe(LeaveStatus::Pending);
    });

    it('keeps the history append-only', function () {
        $change = LeaveRequest::factory()->create()->statusChanges()->create(['from_status' => null, 'to_status' => LeaveStatus::Pending]);

        expect(fn () => $change->update(['note' => 'edited']))->toThrow(LogicException::class)
            ->and(fn () => $change->delete())->toThrow(LogicException::class)
            ->and(LeaveStatusChange::query()->count())->toBe(1);
    });
});

it('shows the leave credits and recent requests on the employee profile', function () {
    $this->seed(LeavePolicySeeder::class);
    $employee = Employee::factory()->create();
    LeaveRequest::factory()->approved()->create(['employee_id' => $employee->id, 'start_date' => now()->startOfYear()->addDays(40)->toDateString(), 'end_date' => now()->startOfYear()->addDays(41)->toDateString(), 'days' => 2]);

    $this->actingAs(hrStaff())->get(route('employees.show', $employee))
        ->assertSee('Leave for '.now()->year)
        ->assertSee('Vacation Leave')
        ->assertSee('Recent requests')
        ->assertSee('Approved');
});
