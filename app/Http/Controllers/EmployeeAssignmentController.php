<?php

namespace App\Http\Controllers;

use App\Actions\Employees\EmployeeActionException;
use App\Actions\Employees\TransferEmployee;
use App\Enums\AuditAction;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Changes to where an employee works and what they are paid: the HR Head's job. A change of job title, college, or office
 * is a transfer (kept as history, and it may take effect on a future date); a change of salary step is logged.
 */
class EmployeeAssignmentController extends Controller
{
    public function transfer(Request $request, Employee $employee, TransferEmployee $transferEmployee): RedirectResponse
    {
        Gate::authorize('assign', $employee);

        $data = $request->validate([
            'job_title_id' => ['required', 'integer', 'exists:job_titles,id'],
            'extra_college_id' => ['nullable', 'integer', 'exists:colleges,id'],
            'extra_office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'effective_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $transfer = $transferEmployee->handle(
                $request->user(),
                $employee,
                JobTitle::query()->findOrFail($data['job_title_id']),
                $data['extra_college_id'] ?? null,
                $data['extra_office_id'] ?? null,
                Carbon::parse($data['effective_date']),
                $data['reason'] ?? null,
            );
        } catch (EmployeeActionException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return to_route('employees.show', $employee)->with('success', $transfer->applied_at
            ? 'The transfer took effect.'
            : 'The transfer is recorded and takes effect on '.$transfer->effective_date->format('M j, Y').'.');
    }

    public function step(Request $request, Employee $employee, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('assign', $employee);

        $data = $request->validate(['salary_step' => ['required', 'integer', 'between:1,8']]);

        DB::transaction(function () use ($employee, $data, $audit): void {
            $employee->salary_step = $data['salary_step'];
            $changes = $audit->changesFor($employee);
            $employee->save();

            if ($changes !== []) {
                $audit->log(AuditAction::Update, $employee, $changes);
            }
        });

        return to_route('employees.show', $employee)->with('success', 'Salary step saved.');
    }
}
