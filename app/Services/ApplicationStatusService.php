<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Events\ApplicationStatusChanged;
use App\Exceptions\BusinessRuleException;
use App\Models\Application;
use App\Models\StatusHistory;
use App\Models\User;

/**
 * The only place an application's status changes (FR-05). Enforces the
 * allowed transitions, writes the status history and fires the event that
 * drives notifications.
 */
class ApplicationStatusService
{
    public function transition(Application $application, ApplicationStatus $to, ?User $by = null, ?string $remark = null): void
    {
        $from = $application->status;
        if ($from === $to) {
            return;
        }
        if (! $from->canMoveTo($to)) {
            throw new BusinessRuleException("An application can't move from \"{$from->label()}\" to \"{$to->label()}\".");
        }

        $application->status = $to;
        if ($to === ApplicationStatus::Submitted && ! $application->submitted_at) {
            $application->submitted_at = now();
        }
        if ($to->isLocked() && ! $application->locked_at) {
            $application->locked_at = now();
        }
        $application->save();

        StatusHistory::create([
            'application_id' => $application->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $by?->id,
            'remark' => $remark,
        ]);

        ApplicationStatusChanged::dispatch($application, $from, $to);
    }

    /** First history row for a new draft. */
    public function recordCreated(Application $application, ?User $by): void
    {
        StatusHistory::create([
            'application_id' => $application->id,
            'from_status' => null,
            'to_status' => ApplicationStatus::Draft,
            'changed_by' => $by?->id,
        ]);
    }
}
