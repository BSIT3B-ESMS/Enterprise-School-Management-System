<?php

use App\Enums\AuditAction;
use App\Enums\EmployeeStatus;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;

describe('archiving an employee', function () {
    it('marks them with the chosen status, reason, and date and takes them off the list', function () {
        $employee = Employee::factory()->create(['last_name' => 'Leavingperson']);

        $this->actingAs(hrAdmin())
            ->put(route('employees.status', $employee), ['status' => 'resigned', 'reason' => 'Moved abroad', 'effective_on' => '2026-10-01'])
            ->assertRedirect(route('employees.show', $employee));

        $fresh = $employee->fresh();

        expect($fresh->status)->toBe(EmployeeStatus::Resigned)
            ->and($fresh->status_reason)->toBe('Moved abroad')
            ->and($fresh->status_effective_on->toDateString())->toBe('2026-10-01');

        $this->get(route('employees.index'))->assertDontSee('Leavingperson');
        $this->get(route('employees.archived'))->assertSee('Leavingperson')->assertSee('Resigned')->assertSee('Moved abroad');
    });

    it('accepts an inactive status with no reason', function () {
        $employee = Employee::factory()->create();

        $this->actingAs(hrAdmin())->put(route('employees.status', $employee), ['status' => 'inactive'])->assertSessionHasNoErrors();

        expect($employee->fresh()->status)->toBe(EmployeeStatus::Inactive)->and($employee->fresh()->status_reason)->toBeNull();
    });

    it('records the change in the audit log', function () {
        $head = hrAdmin();
        $employee = Employee::factory()->create();

        $this->actingAs($head)->put(route('employees.status', $employee), ['status' => 'retired']);

        $entry = AuditLog::query()->where('entity_id', $employee->id)->where('action', AuditAction::Update)->sole();

        expect($entry->user_id)->toBe($head->id)
            ->and($entry->changes['event'])->toBe('archived')
            ->and($entry->changes['status'])->toEqual(['old' => 'active', 'new' => 'retired']);
    });

    it('turns off the employee login and signs it out', function () {
        $employee = Employee::factory()->create();
        $account = User::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs(hrAdmin())->put(route('employees.status', $employee), ['status' => 'terminated']);

        expect($account->fresh()->is_active)->toBeFalse();
    });

    it('refuses, and changes nothing, when it would leave fewer than two HR Admins', function () {
        $actor = hrAdmin();
        $otherEmployee = Employee::factory()->create();
        User::factory()->hrAdmin()->create(['employee_id' => $otherEmployee->id]);

        $this->actingAs($actor)->put(route('employees.status', $otherEmployee), ['status' => 'resigned'])->assertSessionHas('error');

        expect($otherEmployee->fresh()->status)->toBe(EmployeeStatus::Active);
    });

    it('rejects an unknown status', function () {
        $this->actingAs(hrAdmin())->put(route('employees.status', Employee::factory()->create()), ['status' => 'deleted'])->assertSessionHasErrors('status');
    });

    it('does not delete the record', function () {
        $employee = Employee::factory()->create();

        $this->actingAs(hrAdmin())->put(route('employees.status', $employee), ['status' => 'terminated']);

        expect(Employee::query()->whereKey($employee->id)->exists())->toBeTrue();
    });

    it('is forbidden to HR Staff', function () {
        $employee = Employee::factory()->create();

        $this->actingAs(hrStaff())->put(route('employees.status', $employee), ['status' => 'resigned'])->assertForbidden();

        expect($employee->fresh()->status)->toBe(EmployeeStatus::Active);
    });
});

describe('restoring an employee', function () {
    it('makes them active again and clears the reason, leaving their login off', function () {
        $employee = Employee::factory()->create(['status' => 'resigned', 'status_reason' => 'Moved abroad', 'status_effective_on' => '2026-10-01']);
        $account = User::factory()->create(['employee_id' => $employee->id, 'is_active' => false]);

        $this->actingAs(hrAdmin())->put(route('employees.status', $employee), ['status' => 'active'])->assertSessionHas('success');

        $fresh = $employee->fresh();

        expect($fresh->status)->toBe(EmployeeStatus::Active)
            ->and($fresh->status_reason)->toBeNull()
            ->and($fresh->status_effective_on)->toBeNull()
            ->and($account->fresh()->is_active)->toBeFalse();
    });

    it('refuses to restore someone who is already active', function () {
        $this->actingAs(hrAdmin())->put(route('employees.status', Employee::factory()->create()), ['status' => 'active'])->assertSessionHas('error');
    });
});

describe('the archived page', function () {
    it('searches archived employees and is for the HR Admin only', function () {
        Employee::factory()->inactive()->create(['last_name' => 'Zeppelin']);
        Employee::factory()->inactive()->create(['last_name' => 'Arrowsmith']);

        $this->actingAs(hrAdmin())->get(route('employees.archived', ['q' => 'Zepp']))->assertSee('Zeppelin')->assertDontSee('Arrowsmith');
        $this->actingAs(hrStaff())->get(route('employees.archived'))->assertForbidden();
    });
});
