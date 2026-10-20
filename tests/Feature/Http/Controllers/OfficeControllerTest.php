<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Office;

it('lists each office with its job title and staff counts', function () {
    $office = Office::factory()->create(['code' => 'HR', 'name' => 'Human Resources']);
    $title = JobTitle::factory()->nonTeaching()->create(['office_id' => $office->id]);
    Employee::factory()->create(['job_title_id' => $title->id]);

    $this->actingAs(hrStaff())->get(route('offices.index'))
        ->assertOk()
        ->assertSee('Human Resources')
        ->assertDontSee('Add an office');
});

it('lets the HR Admin add and edit an office, with an audit entry for each', function () {
    $head = hrAdmin();

    $this->actingAs($head)->post(route('offices.store'), ['code' => 'REG', 'name' => 'Registrar'])
        ->assertRedirect(route('offices.index'));

    $office = Office::query()->where('code', 'REG')->sole();

    $this->put(route('offices.update', $office), ['code' => 'REG', 'name' => 'Office of the Registrar', 'is_active' => '1'])
        ->assertRedirect(route('offices.index'));

    expect($office->fresh()->name)->toBe('Office of the Registrar')
        ->and(AuditLog::query()->where('entity_id', $office->id)->pluck('action')->all())
        ->toBe([AuditAction::Create, AuditAction::Update]);
});

it('rejects a duplicate office code', function () {
    Office::factory()->create(['code' => 'HR']);

    $this->actingAs(hrAdmin())->post(route('offices.store'), ['code' => 'HR', 'name' => 'Another'])->assertSessionHasErrors('code');
});

it('forbids HR Staff from adding or editing an office', function () {
    $office = Office::factory()->create();

    $this->actingAs(hrStaff())->post(route('offices.store'), ['code' => 'X', 'name' => 'Nope'])->assertForbidden();
    $this->get(route('offices.edit', $office))->assertForbidden();
    $this->put(route('offices.update', $office), ['code' => 'X', 'name' => 'Nope'])->assertForbidden();
});
