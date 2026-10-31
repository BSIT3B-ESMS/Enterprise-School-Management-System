<?php
namespace App\Http\Controllers;

use App\Services\Audit;
use App\Services\EmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Local/test-only import of a *real EMS-provided* employee export.
 * This is not an employee creation flow and must remain disabled in production.
 */
class EmsRosterImportController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(app()->environment('local', 'testing') && !config('ems.enabled'), 404);
        $request->validate([
            'roster' => 'required|file|mimes:csv,txt|max:2048',
            'confirm_official_ids' => 'accepted',
        ]);
        $file = $request->file('roster');
        $handle = fopen($file->getRealPath(), 'rb');
        if (!$handle) throw ValidationException::withMessages(['roster' => 'Could not read the CSV file.']);
        try {
            $headers = fgetcsv($handle);
            if (!is_array($headers)) throw ValidationException::withMessages(['roster' => 'The CSV is empty.']);
            $headers = array_map(static fn($value) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $value)), $headers);
            $required = ['employee_no', 'full_name', 'department', 'position', 'monthly_rate', 'status', 'salary_effective_on', 'updated_at'];
            if ($headers !== $required) throw ValidationException::withMessages(['roster' => 'Use the exact column order shown in the EMS roster template.']);
            $rows = []; $seen = [];
            while (($cells = fgetcsv($handle)) !== false) {
                if ($cells === [null]) continue;
                if (count($cells) !== count($headers)) throw ValidationException::withMessages(['roster' => 'Every row must have exactly eight CSV columns.']);
                $row = array_combine($headers, array_map('trim', $cells));
                if (isset($seen[$row['employee_no']])) throw ValidationException::withMessages(['roster' => 'Duplicate employee_no in the CSV.']);
                $seen[$row['employee_no']] = true;
                $rows[] = $row;
                if (count($rows) > 5000) throw ValidationException::withMessages(['roster' => 'Import at most 5000 employees per file.']);
            }
            if (!$rows) throw ValidationException::withMessages(['roster' => 'The CSV contains no employee records.']);
        } finally {
            fclose($handle);
        }
        // EmsService applies the same server-side validation used by the live EMS adapter.
        // One invalid row rolls back the entire import.
        $count = DB::transaction(fn() => app(EmsService::class)->importEmployees($rows));
        Audit::record('ems_local_csv_import', 'employees', 'local', ['rows' => $count]);
        return redirect('/?page=workforce')->with('success', "Imported {$count} EMS employee records for local testing. Reconcile with EMS before deployment.");
    }
}
