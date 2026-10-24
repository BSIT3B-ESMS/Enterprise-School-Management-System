<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Application;
use App\Models\InterviewAssignment;
use App\Models\Program;
use App\Models\User;
use App\Notifications\AdmissionNotice;
use App\Services\Dms\DmsSync;
use Illuminate\Support\Facades\DB;

/**
 * Acceptance letter and slot acceptance (FR-09, D14, D16).
 *
 * Final pass (exam, plus interview where the program requires one) →
 * Offered: the applicant is placed in the highest of their three choices
 * that still has slots (FR-21; staff can move them to another of their
 * choices), with a deadline from the period's acceptance window. The applicant accepts before the deadline;
 * the slot is taken only then, under a row lock, so a program never goes
 * over its slots. Unaccepted offers are forfeited by the scheduler.
 */
class OfferService
{
    public function __construct(
        private ApplicationStatusService $status,
        private AuditLogger $audit,
        private DmsSync $dms,
    ) {}

    /** After a passing result is released: walk the three choices (D14). */
    public function afterResultReleased(Application $application, ?User $by): void
    {
        if ($application->status !== ApplicationStatus::Passed) {
            return;
        }
        $this->resolveChoices($application, $by);
    }

    /** The choice being interviewed now: the first interviewing choice without a decision. Later ones wait for a rejection. */
    public function pendingInterview(Application $application): ?Program
    {
        $decided = $application->interviews()->with('schedule')->get()->filter(fn ($i) => $i->outcome)->map(fn ($i) => $i->schedule->program_id);

        return $application->choices()->with('program')->get()->pluck('program')
            ->first(fn (Program $p) => $p->is_open && $p->requires_interview && ! $decided->contains($p->id));
    }

    /**
     * Walk the choices in rank order. A program without an interview accepts
     * the applicant; one with an interview waits for its dean's decision, and
     * a rejection moves on to the next choice. The first accepting program
     * with a free slot gets the offer. Everyone rejected → Interview failed.
     */
    public function resolveChoices(Application $application, ?User $by): void
    {
        $decided = $application->interviews()->with('schedule')->get()->keyBy(fn ($i) => $i->schedule->program_id);
        $filled = Program::filledCounts();
        $rejected = $full = false;

        foreach ($application->choices()->with('program')->get() as $choice) {
            $program = $choice->program;
            if (! $program->is_open) {
                continue;   // closed programs can't take new offers
            }
            if ($program->requires_interview) {
                $outcome = $decided->get($program->id)?->outcome;
                if (! $outcome) {
                    if ($application->status === ApplicationStatus::Passed) {
                        $this->status->transition($application, ApplicationStatus::ForInterview, $by, "{$program->code} requires an interview");
                    }

                    return;
                }
                if ($outcome === InterviewAssignment::FAILED) {
                    $rejected = true;

                    continue;
                }
            }
            if (($filled[$program->id] ?? 0) >= $program->slots) {
                $full = true;

                continue;
            }
            $this->offer($application, $by, $program);

            return;
        }

        if ($rejected && ! $full && $application->status === ApplicationStatus::ForInterview) {
            $this->status->transition($application, ApplicationStatus::InterviewFailed, $by, 'Rejected by every interviewing college');

            return;
        }
        $this->audit->record('offer.no_slot', 'Passed, but none of the three program choices has a free slot. Waiting for placement by admissions', $application, $application);
    }

    /**
     * FR-21: the applicant's highest-ranked choice that is open and still has
     * a free slot (accepted offers count against slots), or null. A 2nd or
     * 3rd choice that needs its own interview is skipped (only the first
     * choice is interviewed).
     */
    public function placementFor(Application $application): ?Program
    {
        $filled = Program::filledCounts();

        return $application->choices()->with('program')->get()->pluck('program')->filter()->values()
            ->first(fn (Program $p, int $i) => ($filled[$p->id] ?? 0) < $p->slots && ($i === 0 || ! $p->requires_interview));
    }

    /**
     * FR-21: admissions places a passer in another of their three choices
     * (before they accept), e.g. when the offered program fills up.
     */
    public function place(Application $application, Program $program, User $staff): void
    {
        if (! $application->choices()->where('program_id', $program->id)->exists()) {
            throw new BusinessRuleException("{$program->code} is not one of this applicant's three choices.");
        }
        if (! in_array($application->status, [ApplicationStatus::Passed, ApplicationStatus::Offered], true)
            || ($application->status === ApplicationStatus::Passed && ! $application->result?->is_released)) {
            throw new BusinessRuleException('Only a released pass that has not accepted a slot yet can be placed.');
        }
        if ((Program::filledCounts()[$program->id] ?? 0) >= $program->slots) {
            throw new BusinessRuleException("All {$program->code} slots have been taken.");
        }

        if ($application->status === ApplicationStatus::Passed) {
            $this->offer($application, $staff, $program);

            return;
        }
        $from = $application->offeredProgram?->code;
        $application->update(['offered_program_id' => $program->id]);
        $this->audit->record('offer.placed', "Placed in {$program->code}".($from ? " instead of {$from}" : ''), $application, $application);
        $this->dms->record('offer.issued', $application, ['program' => $program->code]);
        $application->applicant->user->notify(new AdmissionNotice('offer_changed', 'Your offered program changed',
            "Admissions placed you in {$program->name}. Sign in to the applicant portal to see your updated acceptance letter.", $application->number));
    }

    /** Issue the offer (acceptance letter) for the placed program (FR-21). */
    public function offer(Application $application, ?User $by, ?Program $program = null): void
    {
        $program ??= $this->placementFor($application)
            ?? throw new BusinessRuleException("None of the applicant's program choices has a free slot.");
        $days = $application->period->acceptance_window_days ?: config('admission.offers.default_window_days');
        $deadline = now()->setTimezone(config('admission.display_timezone'))->addDays($days)->endOfDay()->utc();

        $application->fill([
            'offered_program_id' => $program->id,
            'offered_at' => now(),
            'acceptance_deadline' => $deadline,
        ]);
        $this->status->transition($application, ApplicationStatus::Offered, $by, "Offered {$program->code}");
        $this->audit->record('offer.issued', "Issued the acceptance letter for {$program->code} · accept by ".$this->localDate($deadline), $application, $application);
    }

    /**
     * The applicant accepts the slot. The caller has already checked the
     * email code when 2FA at acceptance is on (NFR-04).
     */
    public function accept(Application $application, User $applicant): void
    {
        if ($application->status->holdsSlot()) {
            return; // accepted already (double click)
        }
        if ($application->status === ApplicationStatus::Forfeited) {
            throw new BusinessRuleException('The acceptance deadline passed and this offer was forfeited. Contact the admission office.');
        }
        if ($application->status !== ApplicationStatus::Offered) {
            throw new BusinessRuleException('There is no offer of admission to accept yet.');
        }
        if ($application->acceptance_deadline?->isPast()) {
            throw new BusinessRuleException('The acceptance deadline has passed.');
        }

        DB::transaction(function () use ($application, $applicant) {
            $program = Program::lockForUpdate()->findOrFail($application->offered_program_id);
            $taken = Application::where('offered_program_id', $program->id)
                ->whereIn('status', ApplicationStatus::slotHolding())->count();
            if ($taken >= $program->slots) {
                throw new BusinessRuleException("All {$program->code} slots have been taken. Contact the admission office.");
            }

            $application->offer_accepted_at = now();
            $this->status->transition($application, ApplicationStatus::SlotAccepted, $applicant, "Accepted the {$program->code} slot");
            $this->status->transition($application, ApplicationStatus::ForMedical, $applicant);
            $this->audit->record('offer.accepted', "Accepted the offer of admission · {$program->code} slot", $application, $application);
            $this->dms->record('offer.accepted', $application, ['program' => $program->code, 'accepted_at' => $application->offer_accepted_at->toIso8601String()]);
        });
    }

    /** Scheduler: close offers whose deadline passed (no slot to release: slots are taken on acceptance). */
    public function forfeitExpired(): int
    {
        $expired = Application::where('status', ApplicationStatus::Offered)
            ->where('acceptance_deadline', '<', now())
            ->with('applicant.user')->get();

        foreach ($expired as $application) {
            DB::transaction(function () use ($application) {
                $application->forfeited_at = now();
                $this->status->transition($application, ApplicationStatus::Forfeited, null, 'Acceptance deadline passed');
                $this->audit->record('offer.forfeited', 'Offer forfeited: not accepted by the deadline', $application, $application);
                $this->dms->record('offer.forfeited', $application, ['deadline' => $application->acceptance_deadline->toIso8601String()]);
            });
        }

        return $expired->count();
    }

    /** Scheduler: one reminder when the deadline is close. */
    public function remindDeadlines(): int
    {
        $soon = Application::where('status', ApplicationStatus::Offered)
            ->whereNull('acceptance_reminded_at')
            ->whereBetween('acceptance_deadline', [now(), now()->addDays(config('admission.offers.reminder_days_before'))])
            ->with('applicant.user')->get();

        foreach ($soon as $application) {
            $application->applicant->user->notify(new AdmissionNotice('offer_reminder', 'Reminder: accept your slot',
                'Your offer of admission is waiting. Accept your slot in the applicant portal by '.$this->localDate($application->acceptance_deadline).', or it will be forfeited.',
                $application->number));
            $application->update(['acceptance_reminded_at' => now()]);
        }

        return $soon->count();
    }

    public function localDate($at): string
    {
        return $at->copy()->setTimezone(config('admission.display_timezone'))->format('F j, Y');
    }
}
