<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Api\Applicant\DocumentController as ApplicantDocumentController;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Requirement;
use App\Services\DocumentService;
use App\Services\Documents\DocumentStorage;
use App\Http\Presenters\OfficeStatePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Document review screen and Medical screen of the admission office panel
 * (FR-04, FR-13). Applications are addressed by number (AD-2026-00001),
 * the id the panel already uses.
 */
class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documents,
        private OfficeStatePresenter $presenter,
    ) {}

    public function review(Request $request, Application $application, string $key): JsonResponse
    {
        return $this->decide($request, $application, $key, Requirement::STAGE_ADMISSION);
    }

    public function reviewMedical(Request $request, Application $application, string $key): JsonResponse
    {
        return $this->decide($request, $application, $key, Requirement::STAGE_MEDICAL);
    }

    public function verifyPending(Request $request, Application $application): JsonResponse
    {
        $n = $this->documents->verifyPending($application, Requirement::STAGE_ADMISSION, $request->user());

        return response()->json(['data' => $this->presenter->applicant($application->fresh()), 'verified' => $n]);
    }

    public function verifyPendingMedical(Request $request, Application $application): JsonResponse
    {
        $n = $this->documents->verifyPending($application, Requirement::STAGE_MEDICAL, $request->user());

        return response()->json(['data' => $this->presenter->applicant($application->fresh()), 'verified' => $n]);
    }

    /** Open the uploaded file. Medical files: clinic only (confidential). */
    public function file(Request $request, Application $application, string $key, DocumentStorage $storage): StreamedResponse
    {
        $requirement = Requirement::where('key', $key)->firstOrFail();
        $allowed = $requirement->stage === Requirement::STAGE_MEDICAL ? 'clinic' : 'admission';
        abort_unless($request->user()->hasRole($allowed), 403, 'You can’t open this file.');
        $document = $this->documents->current($application, $requirement) ?? abort(404);

        return ApplicantDocumentController::stream($storage, $document);
    }

    private function decide(Request $request, Application $application, string $key, string $stage): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['verified', 'resubmit'])],
            'remark' => ['nullable', 'string', 'max:500'],
        ]);
        $requirement = $this->documents->requirement($key, $stage);
        $this->documents->review($application, $requirement, $data['status'], $data['remark'] ?? null, $request->user());

        return response()->json(['data' => $this->presenter->applicant($application->fresh())]);
    }
}
