<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\RateTier;

function rateFor(int $grade, float $amount, string $date): void
{
    RateTier::factory()->create(['salary_grade' => $grade, 'monthly_amount' => $amount, 'effective_date' => $date]);
}

function newRate(array $overrides = []): array
{
    return [
        'salary_grade' => 3,
        'effective_date' => '2027-01-01',
        'monthly_amount' => 40000,
        ...$overrides,
    ];
}

beforeEach(function () {
    rateFor(3, 30000, '2026-01-01');
});

it('shows the pay in force today for every salary grade, with the titles that use them', function () {
    JobTitle::factory()->create(['title' => 'Registrar Clerk', 'salary_grade' => 3]);

    $this->actingAs(hrStaff())->get(route('rates.index'))
        ->assertOk()
        ->assertSee('Registrar Clerk')
        ->assertSee('30,000');
});

it('shows the current pay, not a future-dated one, in the table', function () {
    rateFor(3, 50000, now()->addYear()->toDateString());

    $this->actingAs(hrAdmin())->get(route('rates.index'))->assertSee('30,000')->assertDontSee('50,000');
});

it('lists every earlier rate in the history of a grade', function () {
    rateFor(3, 28000, '2025-01-01');

    $this->actingAs(hrAdmin())->get(route('rates.index', ['grade' => 3]))
        ->assertOk()
        ->assertSee('History')
        ->assertSee('Jan 1, 2025')
        ->assertSee('Jan 1, 2026');
});

describe('adding a new rate', function () {
    it('adds the rate from the date and keeps the old one', function () {
        $this->actingAs(hrAdmin())->post(route('rates.store'), newRate())
            ->assertRedirect(route('rates.index', ['grade' => 3]));

        expect(RateTier::query()->where('salary_grade', 3)->count())->toBe(2)
            ->and(RateTier::currentFor(3, now()->setDate(2026, 6, 1))->monthly_amount)->toBe('30000.00')
            ->and(RateTier::currentFor(3, now()->setDate(2027, 6, 1))->monthly_amount)->toBe('40000.00');
    });

    it('changes what an existing employee is paid from the new date without touching the employee', function () {
        $employee = Employee::factory()->for(JobTitle::factory()->state(['salary_grade' => 3]))->create();

        $this->actingAs(hrAdmin())->post(route('rates.store'), newRate());

        expect($employee->baseRate(now()->setDate(2026, 6, 1))->monthly_amount)->toBe('30000.00')
            ->and($employee->baseRate(now()->setDate(2027, 6, 1))->monthly_amount)->toBe('40000.00');
    });

    it('records the change in the audit log with the amount', function () {
        $head = hrAdmin();

        $this->actingAs($head)->post(route('rates.store'), newRate());

        $entry = AuditLog::query()->where('action', AuditAction::Create)->where('user_id', $head->id)->latest('id')->first();

        expect($entry->changes['salary_grade'])->toBe(3)
            ->and($entry->changes['effective_date'])->toBe('2027-01-01')
            ->and($entry->changes['monthly_amount'])->toEqual(40000);
    });

    it('rejects :scenario and saves nothing', function (array $override, string $field) {
        $this->actingAs(hrAdmin())->post(route('rates.store'), newRate($override))->assertSessionHasErrors($field);

        expect(RateTier::query()->count())->toBe(1);
    })->with([
        'a grade that does not exist' => [['salary_grade' => 99], 'salary_grade'],
        'a missing date' => [['effective_date' => ''], 'effective_date'],
        'a date that already has a rate' => [['effective_date' => '2026-01-01'], 'effective_date'],
        'a missing amount' => [['monthly_amount' => ''], 'monthly_amount'],
        'a negative amount' => [['monthly_amount' => -1], 'monthly_amount'],
    ]);

    it('is forbidden to HR Staff', function () {
        $this->actingAs(hrStaff())->post(route('rates.store'), newRate())->assertForbidden();

        expect(RateTier::query()->count())->toBe(1);
    });

    it('shows the form to the HR Admin only', function () {
        $this->actingAs(hrAdmin())->get(route('rates.index', ['grade' => 3]))->assertSee('Save New Rate');
        $this->actingAs(hrStaff())->get(route('rates.index', ['grade' => 3]))->assertDontSee('Save New Rate');
    });
});
