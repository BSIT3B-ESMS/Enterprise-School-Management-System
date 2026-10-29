<?php

namespace App\Actions\Leave;

use App\Enums\AuditAction;
use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Support\Facades\DB;

class DecideLeaveRequest
{
    public function __construct(private AuditLogger $audit, private Notifier $notifier) {}

    /**
     * Approves or rejects a pending request. The decision is final: the request row is not changed again, and the history
     * keeps who decided and why. A rejection needs a reason. Nobody decides their own request.
     *
     * @throws LeaveActionException
     */
    public function handle(User $actor, LeaveRequest $request, LeaveStatus $decision, ?string $note): void
    {
        if (! $actor->isHrAdmin()) {
            throw new LeaveActionException('Only an HR Admin can approve or reject leave.');
        }

        if (! in_array($decision, [LeaveStatus::Approved, LeaveStatus::Rejected], true)) {
            throw new LeaveActionException('A request can only be approved or rejected.');
        }

        if ($actor->employee_id !== null && $actor->employee_id === $request->employee_id) {
            throw new LeaveActionException('You cannot decide your own leave request. Another HR Admin must.');
        }

        if ($decision === LeaveStatus::Rejected && blank($note)) {
            throw new LeaveActionException('Give a reason for rejecting the request.');
        }

        DB::transaction(function () use ($actor, $request, $decision, $note): void {
            // Re-read the status inside the transaction, so two people deciding at once cannot both succeed.
            $current = LeaveRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($current->status->isFinal()) {
                throw new LeaveActionException("This request was already {$current->status->value}.");
            }

            $current->forceFill([
                'status' => $decision,
                'decided_by' => $actor->id,
                'decided_at' => now(),
                'decision_note' => filled($note) ? $note : null,
            ])->save();

            $current->statusChanges()->create([
                'from_status' => LeaveStatus::Pending,
                'to_status' => $decision,
                'changed_by' => $actor->id,
                'note' => filled($note) ? $note : null,
            ]);

            $this->audit->log(AuditAction::Update, $current, ['event' => "leave_{$decision->value}", 'note' => $note], $actor);

            $this->notifier->notify(
                $current->employee->user,
                $decision === LeaveStatus::Approved ? 'Your leave was approved' : 'Your leave was rejected',
                "{$current->leave_type->label()}, {$current->start_date->format('M j')} to {$current->end_date->format('M j, Y')}.".(filled($note) ? " HR: {$note}" : ''),
                $current->employee->user?->isHr() ? route('my-leave') : route('employee.leave'),
            );

            $request->setRawAttributes($current->getAttributes(), true);
        });
    }
}
