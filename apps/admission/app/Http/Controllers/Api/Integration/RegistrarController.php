<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Services\RegistrarExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Registrar / Enrollment pulls admitted applicants (FR-29). X-API-Key of
 * the API client "registrar". The same rows are downloadable by staff as
 * the "Admitted applicants (Registrar)" report in CSV or Excel.
 */
class RegistrarController extends Controller
{
    /** GET ?since=2027-03-01T00:00:00Z (admitted at or after), &page=1 (500 per page) */
    public function admitted(Request $request, RegistrarExportService $export): JsonResponse
    {
        $data = $request->validate(['since' => ['nullable', 'date'], 'page' => ['nullable', 'integer', 'min:1']]);
        $page = $export->admitted($data['since'] ?? null)->paginate(500, page: $data['page'] ?? 1);

        return response()->json([
            'data' => collect($page->items())->map(fn ($row) => $export->record($row))->all(),
            'meta' => ['page' => $page->currentPage(), 'pages' => $page->lastPage(), 'total' => $page->total(), 'columns' => RegistrarExportService::COLUMNS],
        ]);
    }
}
