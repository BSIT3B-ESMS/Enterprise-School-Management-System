<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Models\AdmissionPeriod;
use App\Models\Barangay;
use App\Models\ExamSession;
use App\Models\Strand;
use App\Services\ProgramSuggestionService;
use App\Models\Program;
use App\Models\Requirement;
use App\Services\AdmissionSetupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** "Programs & requirements" screen (FR-09). Admission personnel only. */
class SetupController extends Controller
{
    public function __construct(private AdmissionSetupService $setup) {}

    public function updateProgram(Request $request, Program $program): JsonResponse
    {
        $data = $request->validate([
            'slots' => ['sometimes', 'integer', 'between:1,2000'],
            'name' => ['sometimes', 'string', 'max:255'],
            'open' => ['sometimes', 'boolean'],
            'requiresInterview' => ['sometimes', 'boolean'],
        ], ['slots.between' => 'Slots must be between 1 and 2,000.']);
        if (isset($data['slots'])) {
            $program = $this->setup->setSlots($program, $data['slots']);
        }
        $changes = array_filter(['name' => $data['name'] ?? null, 'is_open' => $data['open'] ?? null, 'requires_interview' => $data['requiresInterview'] ?? null], fn ($v) => $v !== null);
        if ($changes) {
            $program = $this->setup->updateProgram($program, $changes);
        }

        return response()->json(['data' => ['code' => $program->code, 'name' => $program->name, 'slots' => $program->slots, 'open' => $program->is_open, 'requiresInterview' => $program->requires_interview]]);
    }

    public function storeProgram(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+(-[A-Za-z0-9]+)*$/'],
            'name' => ['required', 'string', 'max:255'],
            'slots' => ['required', 'integer', 'between:1,2000'],
        ]);
        $program = $this->setup->addProgram($data['code'], $data['name'], $data['slots']);

        return response()->json(['data' => ['code' => $program->code, 'name' => $program->name, 'slots' => $program->slots]], 201);
    }

    public function updateRequirement(Request $request, string $key): JsonResponse
    {
        $data = $request->validate(['active' => ['required', 'boolean']]);
        $requirement = Requirement::admission()->where('key', $key)->firstOrFail();
        $this->setup->setRequirementActive($requirement, $data['active']);

        return response()->json(['data' => ['key' => $requirement->key, 'active' => $requirement->is_active]]);
    }

    public function updatePeriod(Request $request): JsonResponse
    {
        $data = $request->validate([
            'deadline' => ['sometimes', 'date_format:Y-m-d'],
            'label' => ['sometimes', 'string', 'max:100'],
            'opensOn' => ['sometimes', 'date_format:Y-m-d'],
            'examStartsOn' => ['sometimes', 'date_format:Y-m-d'],
            'examEndsOn' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:examStartsOn'],
            'resultsReleaseOn' => ['sometimes', 'date_format:Y-m-d'],
            'acceptanceWindowDays' => ['sometimes', 'integer', 'between:1,90'],
        ]);
        $period = AdmissionPeriod::current();
        if (isset($data['deadline'])) {
            $this->setup->setDeadline($period, $data['deadline']);
        }
        $map = ['label' => 'label', 'opensOn' => 'opens_on', 'examStartsOn' => 'exam_starts_on', 'examEndsOn' => 'exam_ends_on',
            'resultsReleaseOn' => 'results_release_on', 'acceptanceWindowDays' => 'acceptance_window_days'];
        $changes = [];
        foreach ($map as $field => $column) {
            if (array_key_exists($field, $data)) {
                $changes[$column] = $data[$field];
            }
        }
        if ($changes) {
            $this->setup->updatePeriod($period, $changes);
        }

        return response()->json(['data' => [
            'deadline' => $period->application_deadline->toDateString(),
            'period' => $period->label,
            'inactiveDocs' => Requirement::admission()->where('is_active', false)->pluck('key')->all(),
            'opensOn' => $period->opens_on?->toDateString(),
            'examStartsOn' => $period->exam_starts_on?->toDateString(),
            'examEndsOn' => $period->exam_ends_on?->toDateString(),
            'resultsReleaseOn' => $period->results_release_on?->toDateString(),
            'acceptanceWindowDays' => $period->acceptance_window_days,
        ]]);
    }

    /** FR-06, FR-26: strand → suggested programs, and the barangay list. */
    public function reference(ProgramSuggestionService $suggestions): JsonResponse
    {
        return response()->json(['data' => [
            'strands' => Strand::with(['programs' => fn ($q) => $q->orderBy('code')])->orderBy('sort_order')->get()
                ->map(fn (Strand $s) => ['name' => $s->name, 'active' => $s->is_active, 'programs' => $s->programs->pluck('code')->all()])->all(),
            'barangays' => Barangay::byDistrict(),
        ]]);
    }

    /** PUT /api/admin/strands  Body: { name, programs: ["BSIT", …], active? } */
    public function saveStrand(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'programs' => ['present', 'array', 'max:30'],
            'programs.*' => ['string', 'max:20'],
            'active' => ['sometimes', 'boolean'],
        ]);
        $strand = $this->setup->saveStrand($data['name'], $data['programs'], $data['active'] ?? true);

        return response()->json(['data' => ['name' => $strand->name, 'active' => $strand->is_active, 'programs' => $strand->programs()->orderBy('code')->pluck('code')->all()]]);
    }

    /** PUT /api/admin/barangays/{district}  Body: { names: [...] } (the whole list for the district) */
    public function saveBarangays(Request $request, int $district): JsonResponse
    {
        abort_unless(in_array($district, Barangay::DISTRICTS, true), 404);
        $data = $request->validate(['names' => ['required', 'array', 'min:1', 'max:200'], 'names.*' => ['string', 'max:60']]);

        return response()->json(['data' => ['district' => $district, 'names' => $this->setup->saveBarangays($district, $data['names'])]]);
    }

    /** PATCH /api/admin/exam-sessions/{session}  Body: { venue?, capacity? } */
    public function updateSession(Request $request, string $session): JsonResponse
    {
        $data = $request->validate(['venue' => ['sometimes', 'string', 'max:255'], 'capacity' => ['sometimes', 'integer', 'between:1,500']]);
        $session = $this->setup->updateSession(ExamSession::findOrFail(ExamSession::idFromPublic($session)), $data);

        return response()->json(['data' => ['id' => $session->publicId(), 'venue' => $session->venue, 'capacity' => $session->capacity]]);
    }

    public function deleteSession(string $session): JsonResponse
    {
        $this->setup->deleteSession(ExamSession::findOrFail(ExamSession::idFromPublic($session)));

        return response()->json(['message' => 'Exam session removed.']);
    }
}
