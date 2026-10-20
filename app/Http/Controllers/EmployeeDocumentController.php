<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Models\Employee;
use App\Services\AuditLogger;
use App\Services\DocumentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * HR adds a document to an employee's 201 file. The file is stored in the database and the employee sees it under My Files.
 */
class EmployeeDocumentController extends Controller
{
    public function store(Request $request, Employee $employee, DocumentStore $store, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $data = $request->validate([
            'kind' => ['required', 'in:'.implode(',', array_keys(EmployeePortalController::DOCUMENT_KINDS))],
            'file' => ['required', 'file', 'mimes:'.implode(',', DocumentStore::ALLOWED_EXTENSIONS), 'max:'.DocumentStore::MAX_KILOBYTES],
        ], ['file.max' => 'The file must be 5 MB or smaller.', 'file.required' => 'Choose a file to upload.']);

        DB::transaction(function () use ($employee, $data, $request, $store, $audit): void {
            $document = $store->put($employee, $data['kind'], $request->file('file'), $request->user());
            $audit->log(AuditAction::Create, $document, ['employee_no' => $employee->employee_no, 'kind' => $data['kind']], $request->user());
        });

        return back()->with('success', 'The document was added to the 201 file.');
    }
}
