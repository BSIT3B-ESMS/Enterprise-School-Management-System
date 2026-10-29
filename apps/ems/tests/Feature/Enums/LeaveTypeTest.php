<?php

use App\Enums\LeaveType;
use Illuminate\Support\Carbon;

it('counts only weekdays for most leaves', function (string $start, string $end, int $days) {
    expect(LeaveType::Vacation->daysBetween(Carbon::parse($start), Carbon::parse($end)))->toBe($days);
})->with([
    'a single weekday' => ['2026-03-09', '2026-03-09', 1],
    'a full week' => ['2026-03-09', '2026-03-13', 5],
    'across a weekend' => ['2026-03-06', '2026-03-09', 2],
    'only a weekend' => ['2026-03-07', '2026-03-08', 0],
    'two weeks' => ['2026-03-09', '2026-03-20', 10],
]);

it('counts every calendar day for maternity and special leave for women', function (LeaveType $type) {
    expect($type->daysBetween(Carbon::parse('2026-03-09'), Carbon::parse('2026-03-15')))->toBe(7);
})->with([LeaveType::Maternity, LeaveType::SpecialWomen]);

it('counts paternity leave as 7 working days', function () {
    expect(LeaveType::Paternity->daysBetween(Carbon::parse('2026-03-09'), Carbon::parse('2026-03-17')))->toBe(7);
});

it('counts 105 days for maternity leave from March 9 to June 21', function () {
    expect(LeaveType::Maternity->daysBetween(Carbon::parse('2026-03-09'), Carbon::parse('2026-06-21')))->toBe(105);
});

it('lets only sick and emergency leave start in the past', function (LeaveType $type, bool $allowed) {
    expect($type->allowsBackdating())->toBe($allowed);
})->with([
    'sick' => [LeaveType::Sick, true],
    'emergency' => [LeaveType::Emergency, true],
    'vacation' => [LeaveType::Vacation, false],
    'maternity' => [LeaveType::Maternity, false],
]);
