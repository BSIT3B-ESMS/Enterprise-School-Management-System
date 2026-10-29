<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\EmergencyContact;
use App\Models\Employee;

function contactInput(array $overrides = []): array
{
    return ['name' => 'Lourdes Reyes', 'relationship' => 'Mother', 'phone' => '09171234567', ...$overrides];
}

it('adds a contact, makes the first one primary, and audits it', function () {
    $employee = Employee::factory()->create();

    $this->actingAs(hrStaff())->post(route('emergency-contacts.store', $employee), contactInput())
        ->assertRedirect(route('employees.show', $employee));

    $contact = $employee->emergencyContacts()->sole();

    expect($contact->is_primary)->toBeTrue()
        ->and(AuditLog::query()->where('action', AuditAction::Create)->where('entity_id', $contact->id)->exists())->toBeTrue();
});

it('lets a new primary contact replace the old primary', function () {
    $employee = Employee::factory()->create();
    EmergencyContact::factory()->for($employee)->create(['name' => 'First', 'is_primary' => true]);

    $this->actingAs(hrAdmin())->post(route('emergency-contacts.store', $employee), contactInput(['name' => 'Second', 'is_primary' => '1']));

    expect($employee->emergencyContacts()->where('is_primary', true)->pluck('name')->all())->toBe(['Second']);
});

it('keeps the existing primary when the new contact is not primary', function () {
    $employee = Employee::factory()->create();
    EmergencyContact::factory()->for($employee)->create(['name' => 'First', 'is_primary' => true]);

    $this->actingAs(hrAdmin())->post(route('emergency-contacts.store', $employee), contactInput(['name' => 'Second']));

    expect($employee->emergencyContacts()->where('is_primary', true)->pluck('name')->all())->toBe(['First']);
});

it('rejects :scenario', function (array $override, string $field) {
    $employee = Employee::factory()->create();

    $this->actingAs(hrAdmin())->post(route('emergency-contacts.store', $employee), contactInput($override))->assertSessionHasErrors($field);

    expect($employee->emergencyContacts()->count())->toBe(0);
})->with([
    'a missing name' => [['name' => ''], 'name'],
    'a missing relationship' => [['relationship' => ''], 'relationship'],
    'a bad mobile number' => [['phone' => '555-1234'], 'phone'],
]);

it('removes a contact and audits it', function () {
    $employee = Employee::factory()->create();
    $contact = EmergencyContact::factory()->for($employee)->create();

    $this->actingAs(hrStaff())->delete(route('emergency-contacts.destroy', [$employee, $contact]))
        ->assertRedirect(route('employees.show', $employee));

    expect(EmergencyContact::query()->count())->toBe(0)
        ->and(AuditLog::query()->where('action', AuditAction::Delete)->where('entity_id', $contact->id)->exists())->toBeTrue();
});

it('will not remove another employee\'s contact', function () {
    $employee = Employee::factory()->create();
    $contact = EmergencyContact::factory()->create();

    $this->actingAs(hrAdmin())->delete(route('emergency-contacts.destroy', [$employee, $contact]))->assertNotFound();

    expect(EmergencyContact::query()->count())->toBe(1);
});
