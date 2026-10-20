<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Employee;

function employeeWithTin(): Employee
{
    $employee = Employee::factory()->create();
    $employee->forceFill(['government_ids' => null, 'government_id_hints' => null]);
    $employee->setGovernmentIds(['tin' => '123-456-789-000']);
    $employee->save();

    return $employee;
}

it('shows the full number once to the HR Head who gives a reason, and audits the reveal', function () {
    $head = hrHead();
    $employee = employeeWithTin();

    $this->actingAs($head)->post(route('government-ids.reveal', [$employee, 'tin']), ['reason' => 'government_filing'])
        ->assertRedirect(route('employees.show', $employee))
        ->assertSessionHas('revealed', ['type' => 'tin', 'number' => '123-456-789-000']);

    $entry = AuditLog::query()->where('action', AuditAction::Reveal)->sole();

    expect($entry->user_id)->toBe($head->id)
        ->and($entry->entity_id)->toBe($employee->id)
        ->and($entry->changes)->toEqual(['id_type' => 'tin', 'reason' => 'government_filing'])
        ->and(json_encode($entry->changes))->not->toContain('123-456-789');
});

it('shows the revealed number on the next page only', function () {
    $employee = employeeWithTin();

    $this->actingAs(hrHead())->post(route('government-ids.reveal', [$employee, 'tin']), ['reason' => 'verification']);

    $this->get(route('employees.show', $employee))->assertSee('123-456-789-000');
    $this->get(route('employees.show', $employee))->assertDontSee('123-456-789-000');
});

it('needs one of the listed reasons', function (?string $reason) {
    $employee = employeeWithTin();

    $this->actingAs(hrHead())->post(route('government-ids.reveal', [$employee, 'tin']), ['reason' => $reason])->assertSessionHasErrors('reason');

    expect(AuditLog::query()->where('action', AuditAction::Reveal)->count())->toBe(0);
})->with(['no reason' => null, 'a reason that is not listed' => 'curiosity']);

it('refuses an ID that is not on file, and logs nothing', function () {
    $employee = employeeWithTin();

    $this->actingAs(hrHead())->post(route('government-ids.reveal', [$employee, 'sss']), ['reason' => 'verification'])->assertSessionHas('error');

    expect(AuditLog::query()->where('action', AuditAction::Reveal)->count())->toBe(0);
});

it('is not found for an ID type that does not exist', function () {
    $this->actingAs(hrHead())->post(route('government-ids.reveal', [employeeWithTin(), 'passport']), ['reason' => 'verification'])->assertNotFound();
});

it('is forbidden to HR Staff, who never see the full number', function () {
    $employee = employeeWithTin();

    $this->actingAs(hrStaff())->post(route('government-ids.reveal', [$employee, 'tin']), ['reason' => 'verification'])->assertForbidden();

    expect(AuditLog::query()->where('action', AuditAction::Reveal)->count())->toBe(0);
});
