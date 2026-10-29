<?php

use App\Enums\EmploymentType;
use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Database\Seeders\LeavePolicySeeder;

beforeEach(function () {
    $this->seed(LeavePolicySeeder::class);
    $this->travelTo(now()->setDate(2026, 3, 2)->setTime(9, 0));
});

it('lets HR Staff and HR Admin file their own leave from the HR sidebar', function (string $role) {
    $employee = Employee::factory()->create(['hired_on' => '2025-03-02']);
    $user = User::factory()->{$role}()->create(['employee_id' => $employee->id]);

    $this->actingAs($user)->get(route('my-leave'))->assertOk()->assertSee('My Leave')->assertSee('File a leave request');

    $this->post(route('my-leave.store'), ['leave_type' => 'vacation', 'start_date' => '2026-03-09', 'end_date' => '2026-03-10'])
        ->assertRedirect(route('my-leave'));

    expect(LeaveRequest::query()->sole()->employee_id)->toBe($employee->id);
})->with(['hrStaff', 'hrAdmin']);

it('gives an employee promoted to HR Staff the full-time leave types', function () {
    $employee = Employee::factory()->partTime()->create([
        'hired_on' => '2025-03-02',
        'weekly_hours' => 18,
        'contract_ends_on' => now()->addMonths(3)->toDateString(),
    ]);
    $user = User::factory()->create(['employee_id' => $employee->id]);

    $this->actingAs(hrAdmin())->put(route('accounts.role', $user), ['role' => 'hr_staff'])->assertSessionHas('success');

    $this->actingAs($user->fresh())->get(route('my-leave'))
        ->assertOk()
        ->assertSee('Special Privilege Leave')
        ->assertDontSee('% of the full-time vacation and sick leave');

    expect($employee->fresh()->employment_type)->toBe(EmploymentType::FullTime);
});

it('cancels a pending request of their own but not someone else\'s', function () {
    $employee = Employee::factory()->create();
    $user = User::factory()->hrStaff()->create(['employee_id' => $employee->id]);
    $own = LeaveRequest::factory()->create(['employee_id' => $employee->id]);
    $other = LeaveRequest::factory()->create();

    $this->actingAs($user)->post(route('my-leave.cancel', $own))->assertRedirect(route('my-leave'));
    $this->post(route('my-leave.cancel', $other))->assertForbidden();

    expect($own->fresh()->status)->toBe(LeaveStatus::Cancelled);
});

it('tells the HR Admins when leave is filed and the employee when it is decided', function () {
    $admin = hrAdmin();
    $employee = Employee::factory()->create(['hired_on' => '2025-03-02']);
    $user = User::factory()->create(['employee_id' => $employee->id]);

    $this->actingAs($user)->post(route('employee.leave.store'), ['leave_type' => 'vacation', 'start_date' => '2026-03-09', 'end_date' => '2026-03-10']);
    expect($admin->notifications()->where('data->title', 'New leave request')->count())->toBe(1);

    $this->actingAs($admin)->post(route('leave.approve', LeaveRequest::query()->sole()));
    expect($user->notifications()->where('data->title', 'Your leave was approved')->count())->toBe(1);
});

it('explains when an HR account is not linked to an employee record', function () {
    $this->actingAs(hrAdmin())->get(route('my-leave'))->assertOk()->assertSee('not linked to an employee record');
    $this->post(route('my-leave.store'), [])->assertForbidden();
});

it('is closed to the Employee role', function () {
    $this->actingAs(employeeUser())->get(route('my-leave'))->assertForbidden();
});
