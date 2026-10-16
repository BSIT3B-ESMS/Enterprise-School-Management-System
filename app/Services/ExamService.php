<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Events\ExamScheduled;
use App\Events\ResultReleased;
use App\Exceptions\BusinessRuleException;
use App\Models\AdmissionPeriod;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ExamRegistration;
use App\Models\ExamResult;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pen-and-paper entrance exam (FR-06, decision D1) and results (FR-08).
 * Nothing is taken or scored online: staff schedule, check attendance at
 * the venue and record the score from the marked paper. Scores stay
 * internal (NFR-02).
 */
class ExamService
{
    public function __construct(
        private ApplicationService $applications,
        private ApplicationStatusService $status,
        private AuditLogger $audit,
    ) {}

    public function createSession(string $date, string $batch, string $venue, int $capacity, User $staff): ExamSession
    {
        $venue = trim($venue);
        $duplicate = ExamSession::whereDate('date', $date)->where('batch', $batch)
            ->whereRaw('LOWER(venue) = ?', [mb_strtolower($venue)])->exists();
        if ($duplicate) {
            throw new BusinessRuleException('That session already exists.');
        }

        $session = ExamSession::create([
            'admission_period_id' => AdmissionPeriod::current()->id,
            'date' => $date, 'batch' => $batch, 'venue' => $venue, 'capacity' => $capacity, 'created_by' => $staff->id,
        ]);
        $this->audit->record('exam.session_created', "Created exam session {$session->label()} at {$venue} ({$capacity} seats)", $session);

        return $session;
    }

    /** All required documents verified and still waiting for review (admin-store.js canApprove). */
    public function canApprove(Application $application): bool
    {
        if ($application->status !== ApplicationStatus::Submitted) {
            return false;
        }
        $docs = $application->documents()->get()->keyBy('requirement_id');

        return $this->applications->requiredDocuments($application)
            ->every(fn ($r) => ($docs[$r->id]->status ?? null) === ApplicationDocument::VERIFIED);
    }

    /** Approve for the exam: assign a session and issue the permit (FR-05, FR-06). */
    public function approve(Application $application, ExamSession $session, User $staff): ExamRegistration
    {
        if (! $this->canApprove($application)) {
            throw new BusinessRuleException('Verify every required document before approving for the exam.');
        }

        $registration = DB::transaction(function () use ($application, $session, $staff) {
            $this->assertSeat($session);
            $registration = ExamRegistration::create([
                'application_id' => $application->id,
                'exam_session_id' => $session->id,
                'permit_no' => $this->permitNumber($application),
                'approved_by' => $staff->id,
            ]);
            $this->status->transition($application, ApplicationStatus::ForExam, $staff);
            $this->audit->record('exam.approved', "Approved for the entrance exam · {$session->label()}", $registration, $application);

            return $registration;
        });
        ExamScheduled::dispatch($application, $session);

        return $registration;
    }

    /** Move an examinee (usually absent) to another session. */
    public function reschedule(Application $application, ExamSession $session, User $staff): void
    {
        $registration = $application->examRegistration;
        if (! $registration || $application->result) {
            throw new BusinessRuleException('Only an examinee without a result can be rescheduled.');
        }

        DB::transaction(function () use ($application, $registration, $session) {
            $this->assertSeat($session);
            $registration->update([
                'exam_session_id' => $session->id, 'attendance' => null, 'attendance_by' => null,
                'attendance_at' => null, 'permit_checked' => false, 'form_checked' => false,
            ]);
            $this->audit->record('exam.rescheduled', "Rescheduled the entrance exam to {$session->label()}", $registration, $application);
        });
        ExamScheduled::dispatch($application, $session);
    }

    /** Venue checklist and attendance. "Present" needs the permit and form checked. */
    public function updateRoster(Application $application, array $changes, User $staff): void
    {
        $registration = $application->examRegistration;
        if (! $registration || $application->result) {
            throw new BusinessRuleException('Attendance can no longer change once a result is recorded.');
        }

        foreach (['permitChecked' => 'permit_checked', 'formChecked' => 'form_checked'] as $field => $column) {
            if (array_key_exists($field, $changes)) {
                $registration->{$column} = (bool) $changes[$field];
            }
        }

        if (array_key_exists('attendance', $changes) && $changes['attendance'] !== $registration->attendance) {
            $value = $changes['attendance'];
            if ($value === 'present' && ! ($registration->permit_checked && $registration->form_checked)) {
                throw new BusinessRuleException('Check the exam permit and the application form before marking present.');
            }
            $registration->fill(['attendance' => $value, 'attendance_by' => $value ? $staff->id : null, 'attendance_at' => $value ? now() : null]);
            $text = match ($value) {
                'present' => 'Marked present at the exam venue (permit and form checked)',
                'absent' => 'Marked absent from the entrance exam',
                default => 'Cleared exam attendance',
            };
            $this->audit->record('exam.attendance', $text, $registration, $application);
        }
        $registration->save();
    }

    /** Record (or correct, before release) the internal score and decision. */
    public function recordResult(Application $application, int $score, string $decision, User $staff): ExamResult
    {
        $registration = $application->examRegistration;
        if (! $registration || $registration->attendance !== 'present') {
            throw new BusinessRuleException('Record a result only for an examinee marked present.');
        }
        if ($application->result?->is_released) {
            throw new BusinessRuleException("A released result can't be changed.");
        }

        return DB::transaction(function () use ($application, $score, $decision, $staff) {
            $changed = $application->result !== null;
            $result = ExamResult::updateOrCreate(['application_id' => $application->id], [
                'score' => $score, 'decision' => $decision, 'recorded_by' => $staff->id, 'recorded_at' => now(), 'is_released' => false,
            ]);
            $this->status->transition($application, ApplicationStatus::from($decision), $staff);
            $label = $decision === 'passed' ? 'Passed' : 'Did not pass';
            $this->audit->record('result.recorded', ($changed ? 'Updated' : 'Recorded')." exam result: {$label}", $result, $application);

            return $result;
        });
    }

    /**
     * Controlled release (FR-08): only released results are visible to
     * applicants. Returns the applications whose result was released.
     *
     * @param  Collection<int, Application>  $applications
     */
    public function release(Collection $applications, User $staff): Collection
    {
        $released = DB::transaction(function () use ($applications, $staff) {
            $released = collect();
            foreach ($applications as $application) {
                $result = $application->result;
                if (! $result || $result->is_released) {
                    continue;
                }
                $result->update(['is_released' => true, 'released_by' => $staff->id, 'released_at' => now()]);
                $this->audit->record('result.released', 'Released the exam result to the applicant', $result, $application);
                $released->push($application);
            }
            if ($n = $released->count()) {
                $this->audit->record('result.released_batch', "Released {$n} exam result".($n === 1 ? '' : 's').' through the system', meta: ['applications' => $released->pluck('number')->all()]);
            }

            return $released;
        });
        $released->each(fn (Application $a) => ResultReleased::dispatch($a));

        return $released;
    }

    /** The applicant accepts the offer of admission (portal "Accept offer"). */
    public function acceptOffer(Application $application): void
    {
        if (! $application->medicalApplies()) {
            throw new BusinessRuleException('There is no offer of admission to accept yet.');
        }
        if ($application->offer_accepted_at) {
            return;
        }
        $application->update(['offer_accepted_at' => now()]);
        $this->audit->record('offer.accepted', 'Accepted the offer of admission', $application, $application);
    }

    private function assertSeat(ExamSession $session): void
    {
        $session = ExamSession::lockForUpdate()->findOrFail($session->id);
        if ($session->registrations()->count() >= $session->capacity) {
            throw new BusinessRuleException('That session is full. Pick another one.');
        }
    }

    /** EX-27-00001: "27" from the academic year (2027–2028) + the application's number. */
    private function permitNumber(Application $application): string
    {
        $year = substr($application->period->academic_year, 2, 2);

        return sprintf('%s-%s-%s', config('admission.permit_prefix'), $year, substr($application->number, -5));
    }
}
