<?php

namespace App\Actions\Leave;

use App\Enums\AuditAction;
use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class CancelLeaveRequest
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Withdraws a pending request. Once HR has decided, the request is final and can no longer be cancelled.
     *
     * @throws LeaveActionException
     */
    public function handle(User $actor, LeaveRequest $request): void
    {
        DB::transaction(function () use ($actor, $request): void {
            $current = LeaveRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($current->status->isFinal()) {
                throw new LeaveActionException("This request is already {$current->status->value} and cannot be cancelled.");
            }

            $current->forceFill(['status' => LeaveStatus::Cancelled])->save();
            $current->statusChanges()->create(['from_status' => LeaveStatus::Pending, 'to_status' => LeaveStatus::Cancelled, 'changed_by' => $actor->id]);
            $this->audit->log(AuditAction::Update, $current, ['event' => 'leave_cancelled'], $actor);

            $request->setRawAttributes($current->getAttributes(), true);
        });
    }
}
