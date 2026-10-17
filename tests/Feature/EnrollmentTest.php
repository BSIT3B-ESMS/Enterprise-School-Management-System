<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\EnrollmentHandoff;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsApplications;
use Tests\TestCase;

/** Enrollment integration (architecture diagram, T9: switched off by default). */
class EnrollmentTest extends TestCase
{
    use BuildsApplications;

    private function enable(): void
    {
        config(['admission.enrollment' => ['enabled' => true, 'base_url' => 'https://enroll.test/api', 'api_key' => 'out-key',
            'inbound_key' => 'in-key', 'timeout' => 5]]);
    }

    /** Released pass → medical cleared by the clinic. */
    private function admitted(): string
    {
        $user = $this->examined('passed');
        $number = $this->number($user);
        $this->asUser($this->officer())->postJson('/api/admin/results/release', ['ids' => [$number]])->assertOk();
        foreach (['medCert', 'xray', 'labs'] as $key) {
            $this->uploadDoc($user, $key, 'medical')->assertCreated();
        }
        $this->asUser($user)->postJson('/api/applicant/medical/submit')->assertOk();
        $this->asUser($this->makeUser(Role::Clinic))->postJson("/api/admin/applicants/{$number}/medical/verify-pending")->assertOk();

        return $number;
    }

    public function test_switched_off_by_default(): void
    {
        Http::fake();
        $this->admitted();

        $this->assertSame(0, EnrollmentHandoff::count());
        Http::assertNothingSent();
    }

    public function test_admitted_applicant_is_sent_with_keys(): void
    {
        $this->enable();
        Http::fake(['enroll.test/api/admitted' => Http::response(['received' => true], 202)]);

        $number = $this->admitted();

        $handoff = EnrollmentHandoff::firstOrFail();
        $this->assertSame(EnrollmentHandoff::SENT, $handoff->status);
        Http::assertSent(fn (HttpRequest $r) => $r->hasHeader('X-API-Key', 'out-key')
            && $r->hasHeader('Idempotency-Key', $handoff->idempotency_key)
            && $r['admission_reference'] === $number
            && $r['program_code'] === 'BSIT');
    }

    public function test_failure_is_recorded_and_retry_reuses_the_idempotency_key(): void
    {
        $this->enable();
        Http::fake(['enroll.test/*' => Http::sequence()->push('down', 503)->push(['student_number' => '2027-00042'], 201)]);

        try {
            $this->admitted();
        } catch (\Throwable) {
            // sync queue: the first attempt's failure surfaces here
        }
        $handoff = EnrollmentHandoff::firstOrFail();
        $this->assertStringContainsString('HTTP 503', (string) $handoff->last_error);

        $this->artisan('enrollment:retry')->assertSuccessful();

        $handoff->refresh();
        $this->assertSame(EnrollmentHandoff::ACKNOWLEDGED, $handoff->status);
        $this->assertSame('2027-00042', $handoff->student_number);
        $keys = collect(Http::recorded())->map(fn ($pair) => $pair[0]->header('Idempotency-Key')[0])->unique();
        $this->assertCount(1, $keys);
    }

    public function test_student_number_callback_needs_the_inbound_key(): void
    {
        $this->enable();
        Http::fake();
        $number = $this->admitted();
        $body = ['admission_reference' => $number, 'student_number' => '2027-00099'];

        $this->postJson('/api/integrations/enrollment/student-number', $body)->assertUnauthorized();
        $this->postJson('/api/integrations/enrollment/student-number', $body, ['X-API-Key' => 'wrong'])->assertUnauthorized();
        $this->postJson('/api/integrations/enrollment/student-number', $body, ['X-API-Key' => 'in-key'])
            ->assertOk()->assertJsonPath('data.student_number', '2027-00099');

        $this->assertDatabaseHas('audit_logs', ['description' => 'Enrollment assigned student number 2027-00099']);
    }
}
