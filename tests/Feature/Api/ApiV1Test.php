<?php

use App\Enums\EmployeeStatus;
use App\Enums\LeaveStatus;
use App\Models\ApiAccessLog;
use App\Models\ApiConsumer;
use App\Models\College;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\LeaveRequest;
use App\Models\RateTier;

/**
 * A consumer with the given abilities and the token that signs in as it.
 *
 * @param  list<string>  $abilities
 * @return array{0: ApiConsumer, 1: string}
 */
function apiConsumer(array $abilities = ['employees:read', 'reference:read']): array
{
    $consumer = new ApiConsumer(['name' => 'Payroll '.fake()->unique()->numerify('###'), 'abilities' => $abilities, 'is_active' => true]);
    $token = $consumer->issueToken();

    return [$consumer, $token];
}

describe('signing in', function () {
    it('answers 401 without a token, with a wrong token, and with a revoked one', function () {
        $this->getJson('/api/v1/employees')->assertUnauthorized();
        $this->withToken('ems_nope')->getJson('/api/v1/employees')->assertUnauthorized();

        [$consumer, $token] = apiConsumer();
        $consumer->update(['is_active' => false]);

        $this->withToken($token)->getJson('/api/v1/employees')->assertUnauthorized();
    });

    it('answers 403 when the token lacks the ability for the route', function () {
        [, $token] = apiConsumer(['reference:read']);

        $this->withToken($token)->getJson('/api/v1/employees')->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/colleges')->assertOk();
    });

    it('stores only a hash of the token and records each request without the token or the data', function () {
        [$consumer, $token] = apiConsumer();

        $this->withToken($token)->getJson('/api/v1/colleges')->assertOk();

        $log = ApiAccessLog::query()->sole();

        expect($consumer->fresh()->token_hash)->not->toBe($token)
            ->and($consumer->fresh()->last_used_at)->not->toBeNull()
            ->and($log->route)->toBe('api/v1/colleges')
            ->and($log->status)->toBe(200)
            ->and(json_encode($log->toArray()))->not->toContain($token);
    });

    it('answers 429 after 60 requests in a minute', function () {
        [, $token] = apiConsumer();

        foreach (range(1, 60) as $ignored) {
            $this->withToken($token)->getJson('/api/v1/colleges')->assertOk();
        }

        $this->withToken($token)->getJson('/api/v1/colleges')->assertStatus(429);
    });

    it('only reads: other methods are not allowed', function () {
        [, $token] = apiConsumer();

        $this->withToken($token)->postJson('/api/v1/employees', [])->assertStatus(405);
        $this->withToken($token)->deleteJson('/api/v1/employees/20-00001')->assertStatus(405);
    });
});

describe('employees', function () {
    it('lists 50 a page with only the approved fields', function () {
        RateTier::factory()->create(['salary_grade' => 12, 'monthly_amount' => 33947, 'effective_date' => '2026-01-01']);
        Employee::factory()->count(55)->create();
        [, $token] = apiConsumer();

        $response = $this->withToken($token)->getJson('/api/v1/employees')->assertOk();

        expect($response->json('data'))->toHaveCount(50)
            ->and($response->json('meta.total'))->toBe(55)
            ->and(array_keys($response->json('data.0')))->toBe([
                'employee_no', 'full_name', 'employment_type', 'college', 'office', 'job_title', 'base_rate', 'status', 'status_detail', 'hired_on', 'updated_at',
            ]);

        $this->withToken($token)->getJson('/api/v1/employees?page=2')->assertOk()->assertJsonCount(5, 'data');
    });

    it('never returns restricted data', function () {
        $employee = Employee::factory()->create(['personal_email' => 'private@example.test', 'phone' => '09171234567']);
        $employee->setGovernmentIds(['tin' => '123-456-789-000']);
        $employee->save();
        [, $token] = apiConsumer();

        $body = $this->withToken($token)->get('/api/v1/employees/'.$employee->employee_no)->assertOk()->getContent();

        foreach (['123-456-789-000', '9000', 'private@example.test', '09171234567', 'government', 'birthdate', 'marital', 'address', 'password', $employee->official_email] as $secret) {
            expect($body)->not->toContain($secret);
        }
    });

    it('gives the base rate of the job title salary grade and the full name', function () {
        RateTier::factory()->create(['salary_grade' => 12, 'monthly_amount' => 33947, 'effective_date' => '2026-01-01']);
        $employee = Employee::factory()->for(JobTitle::factory()->state(['salary_grade' => 12, 'title' => 'Instructor I']))->create(['first_name' => 'Maila', 'middle_name' => 'N.', 'last_name' => 'Unsay']);
        [, $token] = apiConsumer();

        $this->withToken($token)->getJson('/api/v1/employees/'.$employee->employee_no)
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Maila N. Unsay')
            ->assertJsonPath('data.base_rate', 33947)
            ->assertJsonPath('data.job_title', 'Instructor I');
    });

    it('derives the status: active, on_leave while approved leave covers today, and inactive with its detail', function () {
        $onLeave = Employee::factory()->create();
        LeaveRequest::factory()->create(['employee_id' => $onLeave->id, 'status' => LeaveStatus::Approved, 'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addDay()->toDateString()]);
        $resigned = Employee::factory()->create(['status' => EmployeeStatus::Resigned]);
        $active = Employee::factory()->create();
        [, $token] = apiConsumer();

        $this->withToken($token)->getJson("/api/v1/employees/{$onLeave->employee_no}/status")->assertOk()->assertExactJson(['data' => ['employee_no' => $onLeave->employee_no, 'status' => 'on_leave', 'status_detail' => null]]);
        $this->withToken($token)->getJson("/api/v1/employees/{$resigned->employee_no}/status")->assertJsonPath('data.status', 'inactive')->assertJsonPath('data.status_detail', 'resigned');
        $this->withToken($token)->getJson("/api/v1/employees/{$active->employee_no}/status")->assertJsonPath('data.status', 'active');

        expect($this->withToken($token)->getJson('/api/v1/employees?status=on_leave')->json('data.*.employee_no'))->toBe([$onLeave->employee_no])
            ->and($this->withToken($token)->getJson('/api/v1/employees?status=inactive')->json('data.*.employee_no'))->toBe([$resigned->employee_no])
            ->and($this->withToken($token)->getJson('/api/v1/employees?status=active')->json('data.*.employee_no'))->toBe([$active->employee_no]);
    });

    it('filters by college, job title, and updated_since', function () {
        $college = College::factory()->create(['code' => 'ZZZ']);
        $title = JobTitle::factory()->create(['college_id' => $college->id, 'title' => 'Unique Title']);
        $inside = Employee::factory()->create(['job_title_id' => $title->id]);
        Employee::factory()->create();
        [, $token] = apiConsumer();

        expect($this->withToken($token)->getJson('/api/v1/employees?college=ZZZ')->json('data.*.employee_no'))->toBe([$inside->employee_no])
            ->and($this->withToken($token)->getJson('/api/v1/employees?job_title=Unique%20Title')->json('data.*.employee_no'))->toBe([$inside->employee_no])
            ->and($this->withToken($token)->getJson('/api/v1/employees?updated_since='.now()->addDay()->toDateString())->json('data'))->toBe([]);
    });

    it('answers 404 for an unknown employee and 422 for a bad filter', function () {
        [, $token] = apiConsumer();

        $this->withToken($token)->getJson('/api/v1/employees/99-99999')->assertNotFound();
        $this->withToken($token)->getJson('/api/v1/employees?status=asleep')->assertUnprocessable();
    });
});

describe('reference data', function () {
    it('lists colleges, job titles with their base rate, and the rate schedule on a date', function () {
        College::factory()->create(['code' => 'ABC', 'name' => 'College of ABC']);
        RateTier::factory()->create(['salary_grade' => 12, 'monthly_amount' => 30000, 'effective_date' => '2025-01-01']);
        RateTier::factory()->create(['salary_grade' => 12, 'monthly_amount' => 33947, 'effective_date' => '2026-01-01']);
        JobTitle::factory()->create(['salary_grade' => 12]);
        [, $token] = apiConsumer();

        $this->withToken($token)->getJson('/api/v1/colleges')->assertOk()->assertJsonFragment(['code' => 'ABC', 'name' => 'College of ABC']);
        $this->withToken($token)->getJson('/api/v1/job-titles')->assertOk()->assertJsonPath('data.0.base_rate', 33947);
        $this->withToken($token)->getJson('/api/v1/rate-tiers')->assertJsonPath('data.0.monthly_amount', 33947);
        $this->withToken($token)->getJson('/api/v1/rate-tiers?on=2025-06-01')->assertJsonPath('data.0.monthly_amount', 30000);
    });
});

describe('the api:consumer command', function () {
    it('creates a consumer, shows the token once, and the token works', function () {
        $this->artisan('api:consumer', ['action' => 'create', 'name' => 'Payroll', '--ability' => ['employees:read']])->expectsOutputToContain('Token for Payroll')->assertSuccessful();

        $consumer = ApiConsumer::query()->where('name', 'Payroll')->sole();

        expect($consumer->abilities)->toBe(['employees:read'])
            ->and($consumer->token_hash)->toHaveLength(64);
    });

    it('rotates a token so the old one stops working, and revokes and restores a consumer', function () {
        [$consumer, $old] = apiConsumer();

        $this->artisan('api:consumer', ['action' => 'rotate', 'name' => $consumer->name])->assertSuccessful();
        $this->withToken($old)->getJson('/api/v1/colleges')->assertUnauthorized();

        $this->artisan('api:consumer', ['action' => 'revoke', 'name' => $consumer->name])->assertSuccessful();
        expect($consumer->fresh()->is_active)->toBeFalse();

        $this->artisan('api:consumer', ['action' => 'restore', 'name' => $consumer->name])->assertSuccessful();
        expect($consumer->fresh()->is_active)->toBeTrue();
    });

    it('rejects an unknown ability and a duplicate name', function () {
        $this->artisan('api:consumer', ['action' => 'create', 'name' => 'X', '--ability' => ['everything']])->assertExitCode(2);
        apiConsumer();
        [$consumer] = apiConsumer();
        $this->artisan('api:consumer', ['action' => 'create', 'name' => $consumer->name])->assertFailed();
    });
});
