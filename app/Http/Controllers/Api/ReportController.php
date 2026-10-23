<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reports (FR-13): JSON, Excel (.xlsx) or PDF, role-scoped. API only: the
 * panel's own Reports screen keeps its CSV export.
 */
class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->reports->available($request->user())]);
    }

    /** GET /api/reports/{report}?format=json|csv|xlsx|pdf */
    public function show(Request $request, string $report, AuditLogger $audit): Response
    {
        $format = $request->validate(['format' => ['nullable', 'in:json,csv,xlsx,pdf']])['format'] ?? 'json';
        $data = $this->reports->build($report, $request->user());
        if ($format === 'json') {
            return response()->json(['data' => $data]);
        }

        $audit->record('report.exported', "Exported the \"{$data['title']}\" report ({$format})", meta: ['report' => $report]);
        $name = $report.'-'.now()->format('Y-m-d').'.'.$format;

        return match ($format) {
            'csv' => response($this->reports->toCsv($data), 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$name.'"',
            ]),
            'xlsx' => response()->download($this->reports->toXlsx($data), $name)->deleteFileAfterSend(),
            default => Pdf::loadView('pdf.report', ['report' => $data, 'by' => $request->user()->name])->setPaper('a4', 'landscape')->download($name),
        };
    }
}
