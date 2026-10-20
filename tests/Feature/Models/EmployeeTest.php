<?php

use App\Models\College;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Office;
use App\Models\RateTier;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

it('numbers the first employee of a year YY-00001', function () {
    expect(Employee::nextEmployeeNo(Carbon::parse('2026-03-02')))->toBe('26-00001');
});

it('numbers the next employee of the same year one past the highest number, and restarts each year', function () {
    Employee::factory()->create(['employee_no' => '26-00009']);
    Employee::factory()->create(['employee_no' => '26-00010']);
    Employee::factory()->create(['employee_no' => '25-00200']);

    expect(Employee::nextEmployeeNo(Carbon::parse('2026-09-01')))->toBe('26-00011')
        ->and(Employee::nextEmployeeNo(Carbon::parse('2027-01-04')))->toBe('27-00001');
});

it('builds the official email as surname_givenname with no spaces or punctuation', function () {
    expect(Employee::officialEmailFor('De la Cruz', 'María Clara'))->toBe('delacruz_mariaclara@plpasig.edu.ph');
});

it('adds a number to the official email when the address is already taken', function () {
    Employee::factory()->create(['official_email' => 'santos_juan@plpasig.edu.ph']);

    expect(Employee::officialEmailFor('Santos', 'Juan'))->toBe('santos_juan2@plpasig.edu.ph');
});

it('reads the full name in natural order', function () {
    $employee = Employee::factory()->make(['first_name' => 'Maria', 'middle_name' => 'Clara', 'last_name' => 'Santos']);

    expect($employee->full_name)->toBe('Maria Clara Santos');
});

describe('base rate', function () {
    it('uses the newest rate that is already in effect for the job title salary grade (Step 1)', function () {
        RateTier::factory()->create(['salary_grade' => 12, 'monthly_amount' => 30000, 'effective_date' => '2025-01-01']);
        RateTier::factory()->create(['salary_grade' => 12, 'monthly_amount' => 34069, 'effective_date' => '2026-01-01']);
        RateTier::factory()->create(['salary_grade' => 12, 'monthly_amount' => 99999, 'effective_date' => '2099-01-01']);
        $employee = Employee::factory()->for(JobTitle::factory()->state(['salary_grade' => 12]))->create();

        expect($employee->baseRate(now()->setDate(2026, 6, 1))->monthly_amount)->toBe('34069.00')
            ->and($employee->baseRate(now()->setDate(2025, 6, 1))->monthly_amount)->toBe('30000.00');
    });

    it('is null when no rate exists for the grade', function () {
        $employee = Employee::factory()->create();

        expect($employee->baseRate())->toBeNull();
    });
});

describe('government ID numbers', function () {
    it('stores the numbers encrypted and exposes only the last four digits', function () {
        $employee = Employee::factory()->create();
        $employee->setGovernmentIds(['tin' => '123-456-789-000']);
        $employee->save();

        $raw = DB::table('employees')->where('id', $employee->id)->value('government_ids');

        expect($raw)->not->toContain('123456789')->not->toContain('123-456')
            ->and($employee->fresh()->maskedGovernmentIds()['tin'])->toBe('9000')
            ->and($employee->fresh()->government_ids['tin'])->toBe('123-456-789-000');
    });

    it('keeps numbers that are not given again and skips blank ones', function () {
        $employee = Employee::factory()->create();
        $employee->forceFill(['government_ids' => null, 'government_id_hints' => null]);
        $employee->setGovernmentIds(['tin' => '123-456-789-000']);
        $employee->setGovernmentIds(['sss' => '34-1234567-8', 'gsis' => '', 'tin' => null]);

        expect($employee->government_ids)->toBe(['tin' => '123-456-789-000', 'sss' => '34-1234567-8'])
            ->and($employee->maskedGovernmentIds())->toBe(['tin' => '9000', 'sss' => '5678']);
    });

    it('is left out of the serialized employee', function () {
        $employee = Employee::factory()->create();

        expect($employee->toArray())->not->toHaveKey('government_ids')
            ->and($employee->toArray())->toHaveKey('government_id_hints');
    });
});

describe('assignment', function () {
    it('separates faculty from non-teaching staff by job title category', function () {
        $faculty = Employee::factory()->create();
        $staff = Employee::factory()->nonTeaching()->create();

        expect(Employee::query()->faculty()->pluck('id')->all())->toBe([$faculty->id])
            ->and(Employee::query()->nonTeaching()->pluck('id')->all())->toBe([$staff->id]);
    });

    it('rejects an employee that has neither a college nor an office', function () {
        $employee = Employee::factory()->create();

        expect(fn () => DB::table('employees')->where('id', $employee->id)->update(['college_id' => null, 'office_id' => null]))
            ->toThrow(QueryException::class);
    });

    it('accepts an employee who has both a college and an office', function () {
        $employee = Employee::factory()->nonTeaching()->create(['college_id' => College::factory()->create()->id]);

        expect($employee->fresh()->office_id)->not->toBeNull()
            ->and($employee->fresh()->college_id)->not->toBeNull();
    });

    it('rejects a job title that belongs to both a college and an office, or to neither', function () {
        $title = JobTitle::factory()->create();

        expect(fn () => DB::table('job_titles')->where('id', $title->id)->update(['office_id' => Office::factory()->create()->id]))
            ->toThrow(QueryException::class)
            ->and(fn () => DB::table('job_titles')->where('id', $title->id)->update(['college_id' => null]))
            ->toThrow(QueryException::class);
    });
});
