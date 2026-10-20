<?php

namespace App\Http\Controllers;

use App\Actions\Employees\EmployeeActionException;
use App\Actions\Employees\RevealGovernmentId;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Government ID numbers are masked everywhere. Only the HR Head can see one in full, for a stated reason, and every
 * reveal is written to the audit log.
 */
class GovernmentIdController extends Controller
{
    public function reveal(Request $request, Employee $employee, string $type, RevealGovernmentId $reveal): RedirectResponse
    {
        Gate::authorize('revealGovernmentId', $employee);
        abort_unless(in_array($type, Employee::GOVERNMENT_ID_KEYS, true), 404);

        $data = $request->validate(['reason' => ['required', Rule::in(array_keys(RevealGovernmentId::REASONS))]]);

        try {
            $number = $reveal->handle($request->user(), $employee, $type, $data['reason']);
        } catch (EmployeeActionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('employees.show', $employee)->with('revealed', ['type' => $type, 'number' => $number]);
    }
}
