<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\CollegeDean;
use App\Models\Employee;
use App\Models\Program;

it('lists each college with its dean, program codes, and head count', function () {
    $college = College::factory()->create(['code' => 'CCS', 'name' => 'College of Computer Studies']);
    Program::factory()->for($college)->create(['code' => 'BSCS']);
    $dean = Employee::factory()->create(['college_id' => $college->id, 'first_name' => 'Riegie', 'middle_name' => 'D.', 'last_name' => 'Tan']);
    CollegeDean::factory()->create(['college_id' => $college->id, 'employee_id' => $dean->id]);

    $this->actingAs(hrStaff())->get(route('colleges.index'))
        ->assertOk()
        ->assertSee('College of Computer Studies')
        ->assertSee('Riegie D. Tan')
        ->assertSee('BSCS');
});

it('does not list a dean whose term has ended', function () {
    $college = College::factory()->create();
    $former = Employee::factory()->create(['college_id' => $college->id, 'first_name' => 'Former', 'last_name' => 'Dean']);
    CollegeDean::factory()->create(['college_id' => $college->id, 'employee_id' => $former->id, 'ended_on' => '2024-01-01']);

    $this->actingAs(hrAdmin())->get(route('colleges.index'))->assertDontSee('Former Dean');
});

it('shows the add and edit controls to the HR Admin only', function () {
    $college = College::factory()->create();

    $this->actingAs(hrAdmin())->get(route('colleges.index'))
        ->assertSee('Add a college')
        ->assertSee(route('colleges.edit', $college), escape: false);

    $this->actingAs(hrStaff())->get(route('colleges.index'))
        ->assertDontSee('Add a college')
        ->assertDontSee(route('colleges.edit', $college), escape: false);
});

describe('adding a college', function () {
    it('saves it and records it in the audit log', function () {
        $head = hrAdmin();

        $this->actingAs($head)->post(route('colleges.store'), ['code' => 'CAHS', 'name' => 'College of Allied Health'])
            ->assertRedirect(route('colleges.index'));

        $college = College::query()->where('code', 'CAHS')->sole();
        $entry = AuditLog::query()->where('action', AuditAction::Create)->where('entity_id', $college->id)->sole();

        expect($college->is_active)->toBeTrue()
            ->and($entry->user_id)->toBe($head->id);
    });

    it('rejects a duplicate code or name and a missing field', function () {
        College::factory()->create(['code' => 'CCS', 'name' => 'College of Computer Studies']);

        $this->actingAs(hrAdmin())->post(route('colleges.store'), ['code' => 'CCS', 'name' => 'College of Computer Studies'])
            ->assertSessionHasErrors(['code', 'name']);

        $this->post(route('colleges.store'), [])->assertSessionHasErrors(['code', 'name']);
    });

    it('is forbidden to HR Staff', function () {
        $this->actingAs(hrStaff())->post(route('colleges.store'), ['code' => 'XX', 'name' => 'Nope'])->assertForbidden();

        expect(College::query()->count())->toBe(0);
    });
});

describe('editing a college', function () {
    it('changes the name and records the old and new value', function () {
        $college = College::factory()->create(['code' => 'CCS', 'name' => 'Old Name']);

        $this->actingAs(hrAdmin())->put(route('colleges.update', $college), ['code' => 'CCS', 'name' => 'New Name', 'is_active' => '1'])
            ->assertRedirect(route('colleges.index'));

        $entry = AuditLog::query()->where('action', AuditAction::Update)->where('entity_id', $college->id)->sole();

        expect($college->fresh()->name)->toBe('New Name')
            ->and($entry->changes)->toEqual(['name' => ['old' => 'Old Name', 'new' => 'New Name']]);
    });

    it('deactivates a college when the active box is cleared', function () {
        $college = College::factory()->create();

        $this->actingAs(hrAdmin())->put(route('colleges.update', $college), ['code' => $college->code, 'name' => $college->name]);

        expect($college->fresh()->is_active)->toBeFalse();
    });

    it('lets the college keep its own code and name', function () {
        $college = College::factory()->create();

        $this->actingAs(hrAdmin())->put(route('colleges.update', $college), ['code' => $college->code, 'name' => $college->name, 'is_active' => '1'])
            ->assertSessionHasNoErrors();
    });

    it('is forbidden to HR Staff, on the form and on saving', function () {
        $college = College::factory()->create();

        $this->actingAs(hrStaff())->get(route('colleges.edit', $college))->assertForbidden();
        $this->put(route('colleges.update', $college), ['code' => 'ZZ', 'name' => 'Changed'])->assertForbidden();

        expect($college->fresh()->name)->not->toBe('Changed');
    });
});
