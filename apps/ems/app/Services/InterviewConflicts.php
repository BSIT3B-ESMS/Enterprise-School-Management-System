<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Enums\Role;
use App\Models\Application;
use App\Models\Interview;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Finds what a demo interview would overlap. A conflict is not a warning HR can override: the change is refused so HR can
 * pick another time or another interviewer who is free.
 */
class InterviewConflicts
{
    /**
     * @return list<string>
     */
    public function check(User $interviewer, Application $application, CarbonInterface $start, CarbonInterface $end, ?Interview $ignore = null): array
    {
        $conflicts = [];

        $busy = Interview::query()->overlapping($start, $end)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->where('interviewer_id', $interviewer->id)
            ->with('application.applicant.applicantProfile')
            ->get();

        foreach ($busy as $other) {
            $conflicts[] = "{$interviewer->name} already interviews {$other->application->applicant->applicantProfile->full_name} from {$other->starts_at->format('g:i A')} to {$other->ends_at->format('g:i A')} that day.";
        }

        if ($this->isOnLeave($interviewer, $start)) {
            $conflicts[] = "{$interviewer->name} is on approved leave that day.";
        }

        $applicantBusy = Interview::query()->overlapping($start, $end)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->whereHas('application', fn ($query) => $query->where('user_id', $application->user_id))
            ->exists();

        if ($applicantBusy) {
            $conflicts[] = 'The applicant already has another interview at that time.';
        }

        return $conflicts;
    }

    /**
     * Active HR Staff and HR Admin accounts with no conflict at that time.
     *
     * @return Collection<int, User>
     */
    public function freeInterviewers(Application $application, CarbonInterface $start, CarbonInterface $end, ?Interview $ignore = null): Collection
    {
        return User::query()
            ->whereIn('role', [Role::HrAdmin, Role::HrStaff])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => $this->check($user, $application, $start, $end, $ignore) === []);
    }

    private function isOnLeave(User $user, CarbonInterface $day): bool
    {
        return $user->employee_id !== null && LeaveRequest::query()
            ->where('employee_id', $user->employee_id)
            ->where('status', LeaveStatus::Approved)
            ->whereDate('start_date', '<=', $day->toDateString())
            ->whereDate('end_date', '>=', $day->toDateString())
            ->exists();
    }
}
