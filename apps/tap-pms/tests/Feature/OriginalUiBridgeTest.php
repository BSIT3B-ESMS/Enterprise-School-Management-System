<?php

use App\Models\Employee;
use Illuminate\Http\UploadedFile;

it('serves the original TAP sign-in branding but authenticates with the Laravel form', function () {
    userFixture();
    $this->get('/login')->assertOk()
        ->assertSee('class="login-dial"', false)
        ->assertSee('class="login-company"', false)
        ->assertSee('name="identifier"', false)->assertSee('Employee ID / Admin ID')->assertDontSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertDontSee('emp123');
});

it('keeps the original dashboard layout while showing actual server records', function () {
    $employee = employeeFixture();
    $admin = userFixture();
    $this->actingAs($admin)->withSession(['mfa_verified' => true])->get('/')
        ->assertOk()
        ->assertSee('tap-server-ribbon')
        ->assertSee('tap-wordmark')
        ->assertSee('Daily time records')
        ->assertSee('Workforce &amp; RFID', false)
        ->assertDontSee('PMSStore');
    $this->actingAs($admin)->withSession(['mfa_verified' => true])->get('/?page=workforce')
        ->assertOk()->assertSee($employee->employee_no);
});

it('does not publish the old fake-login HTML page', function () {
    $this->get('/tap-pms-ui/index.html')->assertNotFound();
    $this->postJson('/api/v1/sync', ['state' => ['role' => 'admin']])->assertNotFound();
});

it('permits only a real EMS-roster-shaped CSV for offline local testing', function () {
    config()->set('ems.enabled', false);
    $admin = userFixture();
    $csv = "employee_no,full_name,department,position,monthly_rate,status,salary_effective_on,updated_at\n"
        ."26-00009,Sample Imported Employee,Operations,Specialist,31000.00,active,2026-01-01,2026-10-30T08:00:00Z\n";
    $this->actingAs($admin)->withSession(['mfa_verified' => true])->post('/ems/roster-import', [
        'roster' => UploadedFile::fake()->createWithContent('employees.csv', $csv),
        'confirm_official_ids' => '1',
    ])->assertRedirect('/?page=workforce');
    expect(Employee::where('employee_no', '26-00009')->exists())->toBeTrue();
});

it('rejects an invalid local roster without partially updating employees', function () {
    config()->set('ems.enabled', false);
    $admin = userFixture();
    $csv = "employee_no,full_name,department,position,monthly_rate,status,salary_effective_on,updated_at\n"
        ."26-00009,Employee One,Operations,Specialist,31000.00,active,2026-01-01,2026-10-30T08:00:00Z\n"
        ."26-00010,Employee Two,Operations,Specialist,bad_amount,active,2026-01-01,2026-10-30T08:00:00Z\n";
    $this->actingAs($admin)->withSession(['mfa_verified' => true])->post('/ems/roster-import', [
        'roster' => UploadedFile::fake()->createWithContent('employees.csv', $csv),
        'confirm_official_ids' => '1',
    ])->assertSessionHasErrors();
    expect(Employee::whereIn('employee_no', ['26-00009','26-00010'])->count())->toBe(0);
});

it('does not permit local roster upload in production environments', function () {
    $admin = userFixture();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    app()->detectEnvironment(fn() => 'production');
    $this->actingAs($admin)->withSession(['mfa_verified' => true])->post('/ems/roster-import', [])
        ->assertNotFound();
});
