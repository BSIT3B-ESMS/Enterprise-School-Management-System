<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Contracts that are ending or have ended (part-time engagements end with a semester or the academic year). For each, the HR Admin
 * either sends a new contract offer (full-time, or part-time with a new end of contract) or terminates the employee. Nothing happens
 * on its own when a contract passes its end date; it stays here until HR decides.
 */
class ContractController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-reference-data');

        $employees = Employee::query()
            ->with(['jobTitle', 'college', 'office', 'offers' => fn ($offers) => $offers->pending()])
            ->where('status', EmployeeStatus::Active)
            ->whereNotNull('contract_ends_on')
            ->whereDate('contract_ends_on', '<=', now()->addDays(60))
            ->orderBy('contract_ends_on')
            ->paginate(20);

        return view('contracts.index', ['employees' => $employees]);
    }
}
