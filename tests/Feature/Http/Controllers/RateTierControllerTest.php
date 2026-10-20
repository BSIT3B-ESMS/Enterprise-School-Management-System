<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\RateTier;

function rateBand(int $grade, array $amounts, string $date): void
{
    foreach ($amounts as $index => $amount) {
        RateTier::factory()->create(['salary_grade' => $grade, 'step' => $index + 1, 'monthly_amount' => $amount, 'effective_date' => $date]);
    }
}

function newRates(array $overrides = []): array
{
    return [
        'salary_grade' => 3,
        'effective_date' => '2027-01-01',
        'amounts' => [40000, 41000, 42000, 43000, 44000, 45000, 46000, 47000],
        ...$overrides,
    ];
}

beforeEach(function () {
    rateBand(3, [30000, 31000, 32000, 33000, 34000, 35000, 36000, 37000], '2026-01-01');
});

it('shows the rates in force today for every pay band, with the titles that use them', function () {
    JobTitle::factory()->create(['title' => 'Registrar Clerk', 'salary_grade' => 3]);

    $this->actingAs(hrStaff())->get(route('rates.index'))
        ->assertOk()
        ->assertSee('Registrar Clerk')
        ->assertSee('30,000')
        ->assertSee('37,000');
});

it('shows the current rates, not a future-dated set, in the table', function () {
    rateBand(3, [50000, 51000, 52000, 53000, 54000, 55000, 56000, 57000], now()->addYear()->toDateString());

    $this->actingAs(hrHead())->get(route('rates.index'))->assertSee('30,000')->assertDontSee('50,000');
});

it('lists every earlier set of rates in the history of a band', function () {
    rateBand(3, [32000, 33000, 34000, 35000, 36000, 37000, 38000, 39000], '2025-01-01');

    $this->actingAs(hrHead())->get(route('rates.index', ['grade' => 3]))
        ->assertOk()
        ->assertSee('History')
        ->assertSee('Jan 1, 2025')
        ->assertSee('Jan 1, 2026');
});

describe('adding new rates', function () {
    it('adds eight new rates from the date and keeps the old ones', function () {
        $this->actingAs(hrHead())->post(route('rates.store'), newRates())
            ->assertRedirect(route('rates.index', ['grade' => 3]));

        expect(RateTier::query()->where('salary_grade', 3)->count())->toBe(16)
            ->and(RateTier::currentFor(3, 1, now()->setDate(2026, 6, 1))->monthly_amount)->toBe('30000.00')
            ->and(RateTier::currentFor(3, 1, now()->setDate(2027, 6, 1))->monthly_amount)->toBe('40000.00')
            ->and(RateTier::currentFor(3, 8, now()->setDate(2027, 6, 1))->monthly_amount)->toBe('47000.00');
    });

    it('changes what an existing employee is paid from the new date without touching the employee', function () {
        $employee = Employee::factory()->for(JobTitle::factory()->state(['salary_grade' => 3]))->create(['salary_step' => 2]);

        $this->actingAs(hrHead())->post(route('rates.store'), newRates());

        expect($employee->baseRate(now()->setDate(2026, 6, 1))->monthly_amount)->toBe('31000.00')
            ->and($employee->baseRate(now()->setDate(2027, 6, 1))->monthly_amount)->toBe('41000.00');
    });

    it('records the change in the audit log with the amounts', function () {
        $head = hrHead();

        $this->actingAs($head)->post(route('rates.store'), newRates());

        $entry = AuditLog::query()->where('action', AuditAction::Create)->where('user_id', $head->id)->latest('id')->first();

        expect($entry->changes['salary_grade'])->toBe(3)
            ->and($entry->changes['effective_date'])->toBe('2027-01-01')
            ->and($entry->changes['amounts'])->toHaveCount(8);
    });

    it('rejects :scenario and saves nothing', function (array $override, string $field) {
        $this->actingAs(hrHead())->post(route('rates.store'), newRates($override))->assertSessionHasErrors($field);

        expect(RateTier::query()->count())->toBe(8);
    })->with([
        'a band that does not exist' => [['salary_grade' => 99], 'salary_grade'],
        'a missing date' => [['effective_date' => ''], 'effective_date'],
        'a date that already has rates' => [['effective_date' => '2026-01-01'], 'effective_date'],
        'seven amounts instead of eight' => [['amounts' => [1, 2, 3, 4, 5, 6, 7]], 'amounts'],
        'a negative amount' => [['amounts' => [-1, 2, 3, 4, 5, 6, 7, 8]], 'amounts.0'],
        'steps that go down' => [['amounts' => [40000, 39000, 42000, 43000, 44000, 45000, 46000, 47000]], 'amounts'],
    ]);

    it('is forbidden to HR Staff', function () {
        $this->actingAs(hrStaff())->post(route('rates.store'), newRates())->assertForbidden();

        expect(RateTier::query()->count())->toBe(8);
    });

    it('shows the form to the HR Head only', function () {
        $this->actingAs(hrHead())->get(route('rates.index', ['grade' => 3]))->assertSee('Save New Rates');
        $this->actingAs(hrStaff())->get(route('rates.index', ['grade' => 3]))->assertDontSee('Save New Rates');
    });
});
