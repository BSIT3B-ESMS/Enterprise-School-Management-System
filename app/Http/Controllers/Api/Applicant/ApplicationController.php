<?php

namespace App\Http\Controllers\Api\Applicant;

use App\Http\Controllers\Controller;
use App\Http\Presenters\ApplicantPortalPresenter;
use App\Models\Application;
use App\Services\ApplicationService;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The applicant's own application. No IDs in the URL: the application is
 * always the signed-in applicant's, so nobody can address someone else's.
 */
class ApplicationController extends Controller
{
    public function __construct(
        private ApplicationService $applications,
        private ApplicantPortalPresenter $presenter,
    ) {}

    /** Everything the portal shows (replaces the localStorage state). */
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->presenter->present($this->application($request))]);
    }

    /** Autosave of "My application" (FR-01, FR-02). */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'step' => ['sometimes', 'integer', 'between:1,3'],
            'completed' => ['sometimes', 'array'],
            'completed.*' => ['integer', 'between:1,3'],
        ]);

        $application = $this->application($request);
        $skipped = $this->applications->saveForm($application, $request->all(), $request->user());

        return response()->json([
            'data' => $this->presenter->present($application->fresh()),
            'skipped' => (object) $skipped,
        ]);
    }

    /** Send to admissions (FR-03, FR-05). */
    public function submit(Request $request): JsonResponse
    {
        $application = $this->application($request);
        $this->applications->submit($application, $request->user());

        return response()->json([
            'data' => $this->presenter->present($application->fresh()),
            'message' => "Sent to admissions. We'll let you know once it's reviewed.",
        ]);
    }

    /** "Accept offer" on the result screen (FR-08). */
    public function acceptOffer(Request $request, ExamService $exams): JsonResponse
    {
        $application = $this->application($request);
        $exams->acceptOffer($application);

        return response()->json([
            'data' => $this->presenter->present($application->fresh()),
            'message' => 'Offer accepted. Last step: submit your medical requirements.',
        ]);
    }

    private function application(Request $request): Application
    {
        return $this->applications->current($request->user()->applicant);
    }
}
