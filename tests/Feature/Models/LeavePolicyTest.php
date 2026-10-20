<?php

use App\Enums\LeaveType;
use App\Enums\MaritalStatus;
use App\Enums\Sex;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\LeavePolicy;
use Database\Seeders\LeavePolicySeeder;

beforeEach(function () {
    $this->seed(LeavePolicySeeder::class);
});

it('gives full-time staff the statutory credits whatever their job title', function () {
    $instructor = Employee::factory()->create();
    $professor = Employee::factory()->for(JobTitle::factory()->state(['salary_grade' => 20]))->create();

    expect(LeavePolicy::daysFor($instructor, LeaveType::Vacation))->toBe(15.0)
        ->and(LeavePolicy::daysFor($professor, LeaveType::Vacation))->toBe(15.0)
        ->and(LeavePolicy::daysFor($professor, LeaveType::Sick))->toBe(15.0)
        ->and(LeavePolicy::daysFor($professor, LeaveType::Emergency))->toBe(5.0);
});

it('gives part-time staff only vacation and sick leave', function () {
    $partTimer = Employee::factory()->partTime()->create();

    expect(LeavePolicy::daysFor($partTimer, LeaveType::Vacation))->toBe(15.0)
        ->and(LeavePolicy::daysFor($partTimer, LeaveType::Sick))->toBe(15.0)
        ->and(LeavePolicy::daysFor($partTimer, LeaveType::SpecialPrivilege))->toBeNull();
});

it('makes sex-specific leave available only to those the law covers', function (Sex $sex, MaritalStatus $status, LeaveType $type, ?float $expected) {
    $employee = Employee::factory()->create(['sex' => $sex, 'marital_status' => $status]);

    expect(LeavePolicy::daysFor($employee, $type))->toBe($expected);
})->with([
    'maternity for a woman' => [Sex::Female, MaritalStatus::Single, LeaveType::Maternity, 105.0],
    'maternity for a man' => [Sex::Male, MaritalStatus::Married, LeaveType::Maternity, null],
    'paternity for a married man' => [Sex::Male, MaritalStatus::Married, LeaveType::Paternity, 7.0],
    'paternity for a single man' => [Sex::Male, MaritalStatus::Single, LeaveType::Paternity, null],
    'paternity for a woman' => [Sex::Female, MaritalStatus::Married, LeaveType::Paternity, null],
    'special leave for women' => [Sex::Female, MaritalStatus::Married, LeaveType::SpecialWomen, 60.0],
    'VAWC leave for a man' => [Sex::Male, MaritalStatus::Single, LeaveType::Vawc, null],
    'solo parent for anyone' => [Sex::Male, MaritalStatus::Single, LeaveType::SoloParent, 7.0],
]);

it('lets a job-title row override the general credits for that title only', function () {
    $dean = JobTitle::factory()->create();
    LeavePolicy::factory()->create(['leave_type' => LeaveType::Vacation, 'job_title_id' => $dean->id, 'days_per_year' => 20]);

    $deanEmployee = Employee::factory()->for($dean)->create();
    $other = Employee::factory()->create();

    expect(LeavePolicy::daysFor($deanEmployee, LeaveType::Vacation))->toBe(20.0)
        ->and(LeavePolicy::daysFor($deanEmployee, LeaveType::Sick))->toBe(15.0)
        ->and(LeavePolicy::daysFor($other, LeaveType::Vacation))->toBe(15.0);
});

it('ignores an inactive policy', function () {
    LeavePolicy::query()->where('leave_type', LeaveType::Vacation)->update(['is_active' => false]);

    expect(LeavePolicy::daysFor(Employee::factory()->create(), LeaveType::Vacation))->toBeNull();
});
