<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\CollegeTransfer;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Office;

function transferInput(JobTitle $title, array $overrides = []): array
{
    return ['job_title_id' => $title->id, 'effective_date' => now()->toDateString(), 'reason' => 'Reassigned', ...$overrides];
}

describe('transferring an employee', function () {
    it('moves them at once when the date is today, and keeps the transfer as history', function () {
        $head = hrHead();
        $employee = Employee::factory()->create();
        $oldTitle = $employee->jobTitle;
        $newCollege = College::factory()->create();
        $newTitle = JobTitle::factory()->create(['college_id' => $newCollege->id]);

        $this->actingAs($head)->post(route('employees.transfer', $employee), transferInput($newTitle))
            ->assertRedirect(route('employees.show', $employee))
            ->assertSessionHas('success');

        $transfer = $employee->transfers()->sole();

        expect($employee->fresh()->college_id)->toBe($newCollege->id)
            ->and($employee->fresh()->job_title_id)->toBe($newTitle->id)
            ->and($transfer->from_job_title_id)->toBe($oldTitle->id)
            ->and($transfer->to_job_title_id)->toBe($newTitle->id)
            ->and($transfer->from_college_id)->toBe($oldTitle->college_id)
            ->and($transfer->to_college_id)->toBe($newCollege->id)
            ->and($transfer->reason)->toBe('Reassigned')
            ->and($transfer->recorded_by)->toBe($head->id)
            ->and($transfer->applied_at)->not->toBeNull();
    });

    it('audits both the transfer and the change to the employee', function () {
        $employee = Employee::factory()->create();

        $this->actingAs(hrHead())->post(route('employees.transfer', $employee), transferInput(JobTitle::factory()->create()));

        expect(AuditLog::query()->where('entity_id', $employee->id)->where('action', AuditAction::Update)->exists())->toBeTrue()
            ->and(AuditLog::query()->where('action', AuditAction::Create)->where('entity_type', (new CollegeTransfer)->getMorphClass())->exists())->toBeTrue();
    });

    it('records a future-dated transfer without changing the employee yet', function () {
        $employee = Employee::factory()->create();
        $newTitle = JobTitle::factory()->create();

        $this->actingAs(hrHead())->post(route('employees.transfer', $employee), transferInput($newTitle, ['effective_date' => now()->addMonth()->toDateString()]));

        expect($employee->fresh()->job_title_id)->toBe($employee->job_title_id)
            ->and($employee->transfers()->sole()->applied_at)->toBeNull();

        $this->get(route('employees.show', $employee))->assertSee('takes effect on');
    });

    it('moves office staff into a title in another office, keeping an extra college', function () {
        $employee = Employee::factory()->nonTeaching()->create();
        $college = College::factory()->create();
        $newOffice = Office::factory()->create();
        $title = JobTitle::factory()->nonTeaching()->create(['office_id' => $newOffice->id]);

        $this->actingAs(hrHead())->post(route('employees.transfer', $employee), transferInput($title, ['extra_college_id' => $college->id]));

        expect([$employee->fresh()->office_id, $employee->fresh()->college_id])->toBe([$newOffice->id, $college->id]);
    });

    it('refuses a change that is not a change', function () {
        $employee = Employee::factory()->create();

        $this->actingAs(hrHead())->post(route('employees.transfer', $employee), transferInput($employee->jobTitle))
            ->assertSessionHas('error');

        expect($employee->transfers()->count())->toBe(0);
    });

    it('refuses a second transfer while one is waiting', function () {
        $employee = Employee::factory()->create();

        $this->actingAs(hrHead());
        $this->post(route('employees.transfer', $employee), transferInput(JobTitle::factory()->create(), ['effective_date' => now()->addMonth()->toDateString()]));
        $this->post(route('employees.transfer', $employee), transferInput(JobTitle::factory()->create()))->assertSessionHas('error');

        expect($employee->transfers()->count())->toBe(1);
    });

    it('rejects a missing title or date', function () {
        $employee = Employee::factory()->create();

        $this->actingAs(hrHead())->post(route('employees.transfer', $employee), ['reason' => 'x'])->assertSessionHasErrors(['job_title_id', 'effective_date']);
    });

    it('is forbidden to HR Staff', function () {
        $employee = Employee::factory()->create();

        $this->actingAs(hrStaff())->post(route('employees.transfer', $employee), transferInput(JobTitle::factory()->create()))->assertForbidden();

        expect($employee->transfers()->count())->toBe(0);
    });
});

describe('changing the salary step', function () {
    it('saves the step and records the old and new value', function () {
        $employee = Employee::factory()->create();

        $this->actingAs(hrHead())->put(route('employees.step', $employee), ['salary_step' => 4])->assertRedirect(route('employees.show', $employee));

        $entry = AuditLog::query()->where('entity_id', $employee->id)->where('action', AuditAction::Update)->sole();

        expect($employee->fresh()->salary_step)->toBe(4)
            ->and($entry->changes['salary_step'])->toEqual(['old' => 1, 'new' => 4]);
    });

    it('rejects a step outside 1 to 8', function (int $step) {
        $this->actingAs(hrHead())->put(route('employees.step', Employee::factory()->create()), ['salary_step' => $step])->assertSessionHasErrors('salary_step');
    })->with([0, 9]);

    it('is forbidden to HR Staff', function () {
        $employee = Employee::factory()->create();

        $this->actingAs(hrStaff())->put(route('employees.step', $employee), ['salary_step' => 5])->assertForbidden();

        expect($employee->fresh()->salary_step)->toBe(1);
    });
});

describe('the daily transfer job', function () {
    it('applies transfers that are due and leaves future ones waiting', function () {
        $due = Employee::factory()->create();
        $later = Employee::factory()->create();
        $dueTitle = JobTitle::factory()->create();
        $laterTitle = JobTitle::factory()->create();

        $this->actingAs(hrHead());
        $this->post(route('employees.transfer', $due), transferInput($dueTitle, ['effective_date' => now()->addDays(3)->toDateString()]));
        $this->post(route('employees.transfer', $later), transferInput($laterTitle, ['effective_date' => now()->addDays(30)->toDateString()]));

        $this->travel(4)->days();
        $this->artisan('transfers:apply')->expectsOutput('Applied 1 transfer(s).')->assertSuccessful();

        expect($due->fresh()->job_title_id)->toBe($dueTitle->id)
            ->and($due->transfers()->sole()->applied_at)->not->toBeNull()
            ->and($later->fresh()->job_title_id)->not->toBe($laterTitle->id)
            ->and($later->transfers()->sole()->applied_at)->toBeNull();
    });

    it('does nothing the second time', function () {
        $employee = Employee::factory()->create();

        $this->actingAs(hrHead())->post(route('employees.transfer', $employee), transferInput(JobTitle::factory()->create(), ['effective_date' => now()->addDay()->toDateString()]));

        $this->travel(2)->days();
        $this->artisan('transfers:apply')->expectsOutput('Applied 1 transfer(s).');
        $this->artisan('transfers:apply')->expectsOutput('Applied 0 transfer(s).');
    });
});
