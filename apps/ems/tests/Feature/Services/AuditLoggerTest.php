<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogger;

it('records who did what to which record, and from where', function () {
    $actor = User::factory()->hrAdmin()->create();
    $college = College::factory()->create();

    $entry = $this->actingAs($actor)->app->make(AuditLogger::class)
        ->log(AuditAction::Create, $college);

    expect($entry->fresh())
        ->user_id->toBe($actor->id)
        ->actor_type->toBe('user')
        ->action->toBe(AuditAction::Create)
        ->entity_type->toBe($college->getMorphClass())
        ->entity_id->toBe($college->id)
        ->ip_address->toBe('127.0.0.1')
        ->changes->toBeNull();
});

it('records an entry made by nobody as a system action', function () {
    $entry = app(AuditLogger::class)->log(AuditAction::Export);

    expect($entry->fresh())
        ->user_id->toBeNull()
        ->actor_type->toBe('system')
        ->entity_type->toBeNull();
});

it('lists the old and new value of each unsaved change', function () {
    $college = College::factory()->create(['name' => 'College of Old Name']);
    $college->name = 'College of New Name';

    expect(app(AuditLogger::class)->changesFor($college))
        ->toBe(['name' => ['old' => 'College of Old Name', 'new' => 'College of New Name']]);
});

it('keeps passwords and government ID numbers out of the recorded changes', function () {
    $user = User::factory()->create();
    $user->password = 'a-brand-new-password';
    $employee = Employee::factory()->create();
    $employee->setGovernmentIds(['tin' => '123-456-789-000']);

    $logger = app(AuditLogger::class);

    expect($logger->changesFor($user))->toBe([])
        ->and($logger->changesFor($employee))->not->toHaveKey('government_ids')
        ->and($logger->changesFor($employee))->toHaveKey('government_id_hints');
});

it('refuses to edit or delete an audit entry', function () {
    $entry = AuditLog::factory()->create();

    expect(fn () => $entry->update(['action' => AuditAction::Delete]))->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class)
        ->and(AuditLog::query()->count())->toBe(1);
});
