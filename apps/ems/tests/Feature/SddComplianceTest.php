<?php

use App\Enums\Role;
use App\Models\ApiConsumer;
use App\Models\Employee;
use App\Models\User;

function sddApiConsumer(array $abilities = ['employees:read', 'reference:read']): array
{
    $consumer = new ApiConsumer(['name' => 'Payroll '.fake()->unique()->numerify('###'), 'abilities' => $abilities, 'is_active' => true]);
    $token = $consumer->issueToken();

    return [$consumer, $token];
}

it('applies the SDD role matrix to protected web routes and account actions', function () {
    $employee = Employee::factory()->create();
    $otherEmployee = Employee::factory()->create();
    $head = hrAdmin();

    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->actingAs($head)->get(route('accounts.index'))->assertOk();
    $this->actingAs(hrStaff())->get(route('accounts.index'))->assertForbidden();

    $this->actingAs(employeeUser())->get(route('employee.profile'))->assertOk();
    $this->actingAs(employeeUser())->get(route('employees.show', $otherEmployee))->assertForbidden();

    $this->actingAs(applicantUser())->get(route('applicant.dashboard'))->assertOk();
    $this->actingAs(applicantUser())->get(route('employees.show', $employee))->assertForbidden();

    $this->actingAs($head)->put(route('accounts.role', hrStaff()), ['role' => Role::Employee->value])->assertSessionHas('success');
    $this->actingAs($head)->put(route('accounts.role', $head), ['role' => Role::HrStaff->value])->assertForbidden();
});

it('keeps employee self-service bounded to their own profile and permitted fields', function () {
    $employee = Employee::factory()->create();
    $other = Employee::factory()->create();
    $user = User::factory()->create(['employee_id' => $employee->id]);

    $this->actingAs($user)
        ->put(route('employee.profile.update'), [
            'phone' => '09171112222',
            'address' => 'New address',
            'personal_email' => 'new@example.com',
            'last_name' => 'Hacker',
        ])
        ->assertSessionHas('success');

    expect($employee->fresh()->phone)->toBe('09171112222')
        ->and($employee->fresh()->address)->toBe('New address')
        ->and($employee->fresh()->personal_email)->toBe('new@example.com')
        ->and($employee->fresh()->last_name)->not->toBe('Hacker');

    $this->actingAs($user)->get(route('employees.show', $other))->assertForbidden();
});

it('rejects privilege-escalation and password-reset attempts from unauthorized roles', function () {
    $head = hrAdmin();
    $target = User::factory()->create();

    $this->actingAs($head)->post(route('accounts.reset-password', $target))->assertSessionHas('issued_password');
    $this->actingAs($head)->post(route('accounts.reset-password', $head))->assertForbidden();

    $this->actingAs(hrStaff())->post(route('accounts.reset-password', User::factory()->create()))->assertForbidden();
    $this->actingAs(employeeUser())->post(route('accounts.reset-password', User::factory()->create()))->assertForbidden();

    $this->actingAs($head)->put(route('accounts.role', $head), ['role' => Role::HrStaff->value])->assertForbidden();
});

it('requires authorized bearer tokens and excludes restricted employee data from the API', function () {
    $employee = Employee::factory()->create([
        'personal_email' => 'private@example.test',
        'phone' => '09171234567',
    ]);
    $employee->setGovernmentIds(['tin' => '123-456-789-000']);
    $employee->save();

    $this->getJson('/api/v1/employees')->assertUnauthorized();

    [, $token] = sddApiConsumer(['reference:read']);
    $this->withToken($token)->getJson('/api/v1/employees')->assertForbidden();

    [, $authorizedToken] = sddApiConsumer();
    $response = $this->withToken($authorizedToken)->getJson('/api/v1/employees/'.$employee->employee_no);
    $body = $response->assertOk()->getContent();

    foreach (['123-456-789-000', 'private@example.test', '09171234567', 'government', 'birthdate', 'marital', 'address', 'official_email'] as $secret) {
        expect($body)->not->toContain($secret);
    }
});
