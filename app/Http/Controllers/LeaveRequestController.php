<?php

namespace App\Http\Controllers;

use App\Actions\Leave\DecideLeaveRequest;
use App\Actions\Leave\LeaveActionException;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\College;
use App\Models\LeaveRequest;
use App\Models\Office;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * HR's view of leave: every request, who it is for, and the buttons to approve or reject a pending one. Open to HR Admin
 * and HR Staff; nobody decides their own request.
 */
class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LeaveRequest::class);

        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected,cancelled,all'],
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:'.implode(',', array_column(LeaveType::cases(), 'value'))],
            'college' => ['nullable', 'integer'],
            'office' => ['nullable', 'integer'],
        ]);
        $status = $filters['status'] ?? 'pending';

        $requests = LeaveRequest::query()
            ->with(['employee.jobTitle', 'employee.college', 'employee.office', 'decider', 'statusChanges.changer'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('leave_type', $type))
            ->whereHas('employee', function ($employees) use ($filters): void {
                $employees
                    ->when($filters['q'] ?? null, fn ($query, $term) => $query->search($term))
                    ->when($filters['college'] ?? null, fn ($query, $college) => $query->where('college_id', $college))
                    ->when($filters['office'] ?? null, fn ($query, $office) => $query->where('office_id', $office));
            })
            ->orderByRaw("status = 'pending' desc")
            ->orderBy('start_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('leave.index', [
            'requests' => $requests,
            'status' => $status,
            'filters' => $filters,
            'pendingCount' => LeaveRequest::query()->where('status', LeaveStatus::Pending)->count(),
            'overdueCount' => LeaveRequest::query()->where('status', LeaveStatus::Pending)->get(['id', 'status', 'created_at'])->filter->isOverdue()->count(),
            'canDecide' => $request->user()->isHrAdmin(),
            'types' => collect(LeaveType::cases())->mapWithKeys(fn (LeaveType $type) => [$type->value => $type->label()])->all(),
            'colleges' => College::query()->orderBy('name')->pluck('name', 'id'),
            'offices' => Office::query()->orderBy('name')->pluck('name', 'id'),
            'ownEmployeeId' => $request->user()->employee_id,
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest, DecideLeaveRequest $decide): RedirectResponse
    {
        Gate::authorize('decide', $leaveRequest);

        return $this->decide($request, $leaveRequest, $decide, LeaveStatus::Approved, null, 'Leave approved.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest, DecideLeaveRequest $decide): RedirectResponse
    {
        Gate::authorize('decide', $leaveRequest);

        $data = $request->validate(['decision_note' => ['required', 'string', 'max:500']], ['decision_note.required' => 'Give a reason for rejecting the request.']);

        return $this->decide($request, $leaveRequest, $decide, LeaveStatus::Rejected, $data['decision_note'], 'Leave rejected.');
    }

    private function decide(Request $request, LeaveRequest $leaveRequest, DecideLeaveRequest $decide, LeaveStatus $decision, ?string $note, string $message): RedirectResponse
    {
        try {
            $decide->handle($request->user(), $leaveRequest, $decision, $note);
        } catch (LeaveActionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $message);
    }
}
