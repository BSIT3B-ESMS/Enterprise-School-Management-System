<?php

namespace App\Services;

use App\Jobs\SendToEnrollment;
use App\Models\Application;
use App\Models\EnrollmentHandoff;
use Illuminate\Support\Str;

/**
 * Integration with the university Enrollment system (architecture diagram;
 * decision T9: built switched off, ENROLLMENT_ENABLED=false). Calls go out
 * with HTTPS + X-API-Key, are queued, and retry with the same
 * Idempotency-Key so Enrollment never receives a duplicate.
 *
 * The Enrollment API isn't published yet: the payload and endpoints in
 * SendToEnrollment are the assumed contract.
 */
class EnrollmentService
{
    public function __construct(private AuditLogger $audit) {}

    public function enabled(): bool
    {
        return (bool) config('admission.enrollment.enabled');
    }

    /** Put an admitted applicant on the hand-off list and queue the call. */
    public function queue(Application $application): ?EnrollmentHandoff
    {
        if (! $this->enabled()) {
            return null;
        }

        $handoff = EnrollmentHandoff::firstOrCreate(
            ['application_id' => $application->id],
            ['status' => EnrollmentHandoff::PENDING, 'idempotency_key' => (string) Str::uuid()],
        );
        if (in_array($handoff->status, [EnrollmentHandoff::PENDING, EnrollmentHandoff::FAILED], true)) {
            SendToEnrollment::dispatch($handoff->id)->afterCommit(); // never undo staff work if Enrollment is down
        }

        return $handoff;
    }

    /** Enrollment's callback: the student number for an admitted applicant. */
    public function acknowledge(Application $application, string $studentNumber): EnrollmentHandoff
    {
        $handoff = EnrollmentHandoff::firstOrCreate(
            ['application_id' => $application->id],
            ['idempotency_key' => (string) Str::uuid()],
        );
        $handoff->update([
            'status' => EnrollmentHandoff::ACKNOWLEDGED,
            'student_number' => $studentNumber,
            'acknowledged_at' => now(),
            'last_error' => null,
        ]);
        $this->audit->record('enrollment.acknowledged', "Enrollment assigned student number {$studentNumber}", $handoff, $application);

        return $handoff;
    }

    /** Re-queue hand-offs that are still pending or failed (scheduler, or by hand). */
    public function retryOutstanding(): int
    {
        if (! $this->enabled()) {
            return 0;
        }
        $ids = EnrollmentHandoff::whereIn('status', [EnrollmentHandoff::PENDING, EnrollmentHandoff::FAILED])->pluck('id');
        $ids->each(fn ($id) => SendToEnrollment::dispatch($id));

        return $ids->count();
    }
}
