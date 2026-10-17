<?php

namespace App\Jobs;

use App\Models\EnrollmentHandoff;
use App\Services\AuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Sends one admitted applicant to Enrollment. Queued with backoff; every
 * attempt carries the same Idempotency-Key, so a retry after a timeout
 * can't create a second record on the Enrollment side.
 */
class SendToEnrollment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    /** @return list<int> seconds between attempts */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function __construct(public int $handoffId) {}

    public function handle(AuditLogger $audit): void
    {
        $handoff = EnrollmentHandoff::with(['application.applicant.user', 'application.choices.program', 'application.period'])->find($this->handoffId);
        if (! $handoff || in_array($handoff->status, [EnrollmentHandoff::SENT, EnrollmentHandoff::ACKNOWLEDGED], true)) {
            return;
        }

        $handoff->increment('attempts');
        $response = Http::baseUrl((string) config('admission.enrollment.base_url'))
            ->withHeaders([
                'X-API-Key' => (string) config('admission.enrollment.api_key'),
                'Idempotency-Key' => $handoff->idempotency_key,
            ])
            ->acceptJson()
            ->timeout((int) config('admission.enrollment.timeout'))
            ->post('/admitted', $this->payload($handoff));

        // Let the queue retry on failure; record why
        if ($response->failed()) {
            $handoff->update(['last_error' => mb_substr("HTTP {$response->status()}: ".$response->body(), 0, 500)]);
            $response->throw();
        }

        $studentNumber = $response->json('student_number');
        $handoff->update([
            'status' => $studentNumber ? EnrollmentHandoff::ACKNOWLEDGED : EnrollmentHandoff::SENT,
            'student_number' => $studentNumber ?: $handoff->student_number,
            'sent_at' => now(),
            'acknowledged_at' => $studentNumber ? now() : null,
            'last_error' => null,
        ]);
        $audit->record('enrollment.sent', 'Sent to the Enrollment system'.($studentNumber ? " (student number {$studentNumber})" : ''), $handoff, $handoff->application);
    }

    public function failed(?Throwable $e): void
    {
        $handoff = EnrollmentHandoff::find($this->handoffId);
        $handoff?->update([
            'status' => EnrollmentHandoff::FAILED,
            // Keep the "HTTP 503: …" note from handle() when there is one
            'last_error' => $handoff->last_error ?? mb_substr((string) $e?->getMessage(), 0, 500),
        ]);
    }

    /** What Enrollment receives: identity, contact, program, and the admission reference. */
    private function payload(EnrollmentHandoff $handoff): array
    {
        $application = $handoff->application;
        $p = $application->applicant;

        return [
            'admission_reference' => $application->number,
            'academic_year' => $application->period->academic_year,
            'program_code' => $application->choices->firstWhere('rank', 1)?->program->code,
            'campus' => $application->campus,
            'lrn' => $p->lrn,
            'last_name' => $p->last_name,
            'first_name' => $p->first_name,
            'middle_name' => $p->middle_name,
            'sex' => $p->sex,
            'birthday' => $p->birthday?->toDateString(),
            'email' => $p->user->email,
            'phone' => $p->phone,
            'address' => implode(', ', array_filter([$p->address, $p->city, $p->province, $p->zip])),
            'last_school' => $p->last_school,
            'strand' => $p->strand,
        ];
    }
}
