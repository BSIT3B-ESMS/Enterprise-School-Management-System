<?php

namespace App\Http\Controllers;

use App\Actions\Employees\ChangeEmployeeStatus;
use App\Actions\Employees\EmployeeActionException;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Archiving and restoring employees (the HR Head's job). Records are never deleted: an archived employee is marked
 * Inactive, Resigned, Retired, or Terminated and can be restored.
 */
class EmployeeStatusController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isHrHead(), 403);

        $term = $request->string('q')->trim()->toString();

        return view('employees.archived', [
            'employees' => Employee::query()
                ->with(['jobTitle', 'college', 'office'])
                ->whereNot('status', EmployeeStatus::Active)
                ->when($term !== '', fn ($query) => $query->search($term))
                ->orderBy('last_name')->orderBy('first_name')
                ->paginate(15)
                ->withQueryString(),
            'term' => $term,
        ]);
    }

    public function update(Request $request, Employee $employee, ChangeEmployeeStatus $changeStatus): RedirectResponse
    {
        Gate::authorize('archive', $employee);

        $data = $request->validate([
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
            'effective_on' => ['nullable', 'date'],
        ]);

        try {
            $changeStatus->handle(
                $request->user(),
                $employee,
                EmployeeStatus::from($data['status']),
                $data['reason'] ?? null,
                isset($data['effective_on']) ? Carbon::parse($data['effective_on']) : null,
            );
        } catch (EmployeeActionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('employees.show', $employee)->with('success', $employee->status === EmployeeStatus::Active ? 'The employee was restored.' : 'The employee was archived.');
    }
}
