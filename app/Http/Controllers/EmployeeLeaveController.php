<?php

namespace App\Http\Controllers;

use App\Actions\Leave\CancelLeaveRequest;
use App\Actions\Leave\FileLeaveRequest;
use App\Actions\Leave\LeaveActionException;
use App\Enums\LeaveType;
use App\Models\LeavePolicy;
use App\Models\LeaveRequest;
use App\Services\LeaveBalances;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * A signed-in person's own leave, for an Employee (at /employee/leave) and for HR Staff and HR Admin (at /my-leave): their
 * credits, their requests and how each one was decided, a form to file a new request, and cancelling one that is still
 * pending. Anyone linked to an employee record may file; only an HR Admin decides.
 */
class EmployeeLeaveController extends Controller
{
    public function index(Request $request, LeaveBalances $balances): View
    {
        $employee = $request->user()->employee;

        return view($this->isHrPage($request) ? 'leave.mine' : 'employee.leave', [
            'employee' => $employee,
            'year' => now()->year,
            'balances' => $employee ? $balances->forEmployee($employee) : [],
            'requests' => $employee
                ? $employee->leaveRequests()->with(['decider', 'statusChanges'])->latest('start_date')->latest('id')->limit(25)->get()
                : collect(),
            'types' => $employee
                ? collect(LeaveType::cases())
                    ->filter(fn (LeaveType $type) => LeavePolicy::daysFor($employee, $type) !== null)
                    ->mapWithKeys(fn (LeaveType $type) => [$type->value => $type->label()])->all()
                : [],
            ...$this->routes($request),
        ]);
    }

    public function store(Request $request, FileLeaveRequest $fileLeave): RedirectResponse
    {
        $employee = $request->user()->employee;

        abort_if($employee === null, 403);

        $data = $request->validate([
            'leave_type' => ['required', Rule::enum(LeaveType::class)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $fileLeave->handle(
            $request->user(),
            $employee,
            LeaveType::from($data['leave_type']),
            Carbon::parse($data['start_date']),
            Carbon::parse($data['end_date']),
            $data['reason'] ?? null,
        );

        return to_route($this->routes($request)['indexRoute'])->with('success', 'Your leave request was sent to an HR Admin.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest, CancelLeaveRequest $cancelLeave): RedirectResponse
    {
        Gate::authorize('cancel', $leaveRequest);

        try {
            $cancelLeave->handle($request->user(), $leaveRequest);
        } catch (LeaveActionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route($this->routes($request)['indexRoute'])->with('success', 'Your leave request was cancelled.');
    }

    private function isHrPage(Request $request): bool
    {
        return $request->routeIs('my-leave*');
    }

    /**
     * @return array{storeRoute: string, cancelRoute: string, indexRoute: string}
     */
    private function routes(Request $request): array
    {
        return $this->isHrPage($request)
            ? ['storeRoute' => 'my-leave.store', 'cancelRoute' => 'my-leave.cancel', 'indexRoute' => 'my-leave']
            : ['storeRoute' => 'employee.leave.store', 'cancelRoute' => 'employee.leave.cancel', 'indexRoute' => 'employee.leave'];
    }
}
