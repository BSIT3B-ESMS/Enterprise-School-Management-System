<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\AdmissionPeriod;
use App\Models\Application;
use App\Models\ApplicationProgramChoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * College Dean module, "Applicant List": submitted applicants of the
 * current period who chose at least one program of the dean's own college
 * (e.g. the CCS dean sees BSIT and BSCS applicants only). Read-only; no
 * exam scores, no contact details or documents.
 */
class DeanController extends Controller
{
    public function applicants(Request $request): JsonResponse
    {
        $college = $request->user()->deanOf;
        $programIds = $college?->programs()->pluck('id') ?? collect();

        $applications = Application::where('admission_period_id', AdmissionPeriod::current()->id)
            ->where('status', '!=', ApplicationStatus::Draft->value)
            ->whereHas('choices', fn ($q) => $q->whereIn('program_id', $programIds))
            ->with(['applicant', 'choices.program', 'result', 'interviews.schedule.program', 'offeredProgram'])
            ->orderBy('submitted_at')->get();

        return response()->json(['data' => [
            'college' => $college ? ['code' => $college->code, 'name' => $college->name] : null,
            'programs' => $college?->programs()->where('is_open', true)->orderBy('code')->get(['code', 'name', 'requires_interview'])
                ->map(fn ($p) => ['code' => $p->code, 'name' => $p->name, 'requiresInterview' => $p->requires_interview])->all() ?? [],
            'applicants' => $applications->map(function (Application $a) use ($programIds) {
                $released = $a->result?->is_released ? $a->result->decision : null;
                // Unreleased results stay with admissions: show "For exam" until release (FR-20)
                $status = in_array($a->status, [ApplicationStatus::Passed, ApplicationStatus::Failed], true) && ! $released
                    ? ApplicationStatus::ForExam : $a->status;

                return [
                    'id' => $a->number,
                    'name' => $a->applicant->fullName(true),
                    'strand' => $a->applicant->strand,
                    'school' => $a->applicant->last_school,
                    'choices' => $a->choices->map(fn (ApplicationProgramChoice $c) => [
                        'rank' => $c->rank, 'code' => $c->program->code, 'inCollege' => $programIds->contains($c->program_id),
                    ])->values()->all(),
                    'status' => $status->value,
                    'statusLabel' => $status->label(),
                    'result' => $released,
                    // The dean's own programs only
                    'interviews' => $a->interviews->filter(fn ($i) => $programIds->contains($i->schedule->program_id))->map(fn ($i) => [
                        'program' => $i->schedule->program->code,
                        'date' => $i->schedule->date->toDateString(),
                        'outcome' => $i->outcome,
                    ])->values()->all(),
                    'offeredProgram' => $a->offeredProgram?->code,
                    'submittedAt' => $a->submitted_at?->copy()->setTimezone(config('admission.display_timezone'))->toDateString(),
                ];
            })->values()->all(),
        ]]);
    }
}
