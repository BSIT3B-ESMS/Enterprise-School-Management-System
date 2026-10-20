<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Records every report export in the audit log before the browser builds the file. HR Head only.
 */
class ExportLogController extends Controller
{
    public function store(Request $request, AuditLogger $audit): Response
    {
        $data = $request->validate([
            'report' => ['required', 'in:employees,recruitment,audit'],
            'format' => ['required', 'in:pdf,xlsx,csv'],
            'count' => ['required', 'integer', 'min:0'],
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string', 'max:50'],
        ]);

        $audit->log(AuditAction::Export, null, $data);

        return response()->noContent();
    }
}
