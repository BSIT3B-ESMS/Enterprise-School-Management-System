<?php

use App\Models\Employee;
use App\Models\User;

function newHire(): User
{
    $employee = Employee::factory()->create();

    return User::factory()->needsOnboarding()->state(['employee_id' => $employee->id])->create();
}

function onboardingInput(array $overrides = []): array
{
    return [
        'contact_name' => 'Ana Santos',
        'contact_relationship' => 'Sister',
        'contact_phone' => '09171234567',
        'tin' => '123-456-789-000',
        'gsis' => '12345678901',
        'philhealth' => '12-123456789-1',
        'pagibig' => '1234-5678-9012',
        ...$overrides,
    ];
}

it('shows the setup page to a new hire', function () {
    $this->actingAs(newHire())->get(route('onboarding.show'))->assertOk()->assertSee('Finish setting up your account');
});

it('holds a new hire on the setup page until it is done', function () {
    $this->actingAs(newHire())->get(route('employee.dashboard'))->assertRedirect(route('onboarding.show'));
});

it('saves the emergency contact and encrypted government IDs, then lets the new hire in', function () {
    $user = newHire();

    $this->actingAs($user)->post(route('onboarding.store'), onboardingInput(['sss' => '', 'bir' => '']))
        ->assertRedirect(route('employee.dashboard'));

    $employee = $user->employee->fresh();

    expect($user->fresh()->onboarding_completed_at)->not->toBeNull()
        ->and($employee->emergencyContacts()->where('is_primary', true)->sole()->name)->toBe('Ana Santos')
        ->and($employee->government_ids)->toHaveKeys(['tin', 'gsis', 'philhealth', 'pagibig'])
        ->and($employee->government_ids)->not->toHaveKey('sss');

    $this->get(route('employee.dashboard'))->assertOk();
});

it('requires TIN, GSIS, PhilHealth, Pag-IBIG and the emergency contact but not SSS or BIR', function (string $field) {
    $user = newHire();

    $this->actingAs($user)->post(route('onboarding.store'), onboardingInput([$field => '']))->assertSessionHasErrors($field);

    expect($user->fresh()->onboarding_completed_at)->toBeNull();
})->with(['contact_name', 'contact_relationship', 'contact_phone', 'tin', 'gsis', 'philhealth', 'pagibig']);

it('does not hold an HR Admin on the setup page', function () {
    $this->actingAs(hrAdmin())->get(route('dashboard'))->assertOk();
});

it('redirects someone who already finished setup away from the page', function () {
    $this->actingAs(employeeUser())->get(route('onboarding.show'))->assertRedirect();
});
