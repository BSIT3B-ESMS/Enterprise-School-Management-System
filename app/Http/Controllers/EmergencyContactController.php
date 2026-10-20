<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Models\EmergencyContact;
use App\Models\Employee;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EmergencyContactController extends Controller
{
    public function store(Request $request, Employee $employee, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'relationship' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
        ], ['phone.regex' => 'Enter a valid mobile number: 09XXXXXXXXX or +639XXXXXXXXX.']);

        DB::transaction(function () use ($employee, $data, $request, $audit): void {
            $isPrimary = $request->boolean('is_primary') || $employee->emergencyContacts()->doesntExist();

            if ($isPrimary) {
                $employee->emergencyContacts()->update(['is_primary' => false]);
            }

            $contact = $employee->emergencyContacts()->create([...$data, 'is_primary' => $isPrimary]);
            $audit->log(AuditAction::Create, $contact, ['employee_no' => $employee->employee_no]);
        });

        return to_route('employees.show', $employee)->with('success', 'Emergency contact added.');
    }

    public function destroy(Employee $employee, EmergencyContact $contact, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('update', $employee);
        abort_unless($contact->employee_id === $employee->id, 404);

        DB::transaction(function () use ($employee, $contact, $audit): void {
            $audit->log(AuditAction::Delete, $contact, ['employee_no' => $employee->employee_no]);
            $contact->delete();
        });

        return to_route('employees.show', $employee)->with('success', 'Emergency contact removed.');
    }
}
