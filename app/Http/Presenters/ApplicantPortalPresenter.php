<?php

namespace App\Http\Presenters;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ExamSession;
use App\Models\Requirement;
use App\Services\ApplicationService;
use App\Services\ProgramSuggestionService;
use App\Support\ApplicantForm;

/**
 * Builds the applicant portal's state in the shape applicant.js keeps in
 * localStorage, so the UI renders it unchanged.
 *
 * NFR-02: built from an explicit allow-list. The exam score is never read
 * here, and an unreleased result is never mentioned.
 */
class ApplicantPortalPresenter
{
    public function __construct(
        private ApplicationService $applications,
        private ProgramSuggestionService $suggestions,
    ) {}

    public function present(Application $application): array
    {
        $application->loadMissing(['applicant.user', 'period', 'examRegistration.session', 'result', 'documents.requirement']);
        $progress = $application->form_progress ?? ['step' => 1, 'completed' => []];
        $released = $application->result?->is_released ? $application->result : null;
        $period = $application->period;

        return [
            'applicationId' => $application->number,
            'status' => $application->status->value,
            'phase' => $this->phase($application),
            'step' => (int) $progress['step'],
            'completed' => array_map('intval', $progress['completed']),
            'data' => $this->applications->formValues($application),
            'submittedAt' => $application->submitted_at?->copy()->setTimezone(config('admission.display_timezone'))->toDateString(),
            'accepted' => $application->offer_accepted_at !== null,
            // Official requirements keyed by requirement key. The portal's own cards
            // are not mapped yet (PROGRESS.md, H1).
            'documents' => $this->documents($application, Requirement::STAGE_ADMISSION),
            'exam' => $this->exam($application),
            'result' => $released ? ['decision' => $released->decision, 'releasedAt' => $released->released_at->toDateString()] : null,
            'medical' => $this->medical($application),
            'programs' => ApplicantForm::programNames(),
            'suggestedPrograms' => $this->suggestions->suggestFor($application->applicant)->pluck('name')->all(),
            'keyDates' => array_values(array_filter([
                ['title' => 'Application deadline', 'date' => $period->application_deadline?->toDateString()],
                ['title' => 'Entrance exam days', 'date' => $period->exam_starts_on?->toDateString(), 'end' => $period->exam_ends_on?->toDateString()],
                ['title' => 'Release of results', 'date' => $period->results_release_on?->toDateString()],
                ['title' => 'Reservation fee deadline', 'date' => $period->reservation_deadline?->toDateString()],
            ], fn ($d) => $d['date'] !== null)),
        ];
    }

    /**
     * The portal's four phases (draft → review → exam → result). An application
     * sent back for resubmission reopens as "draft" so the applicant can replace
     * files and submit again with the existing screens.
     */
    private function phase(Application $application): string
    {
        return match ($application->status) {
            ApplicationStatus::Draft, ApplicationStatus::Resubmit => 'draft',
            ApplicationStatus::Submitted => 'review',
            // The portal has no "did not pass" screen yet (matrix discrepancy 4)
            ApplicationStatus::ForExam, ApplicationStatus::Failed => 'exam',
            ApplicationStatus::Passed => $application->result?->is_released ? 'result' : 'exam',
        };
    }

    private function exam(Application $application): ?array
    {
        $reg = $application->examRegistration;
        if (! $reg) {
            return null;
        }

        return [
            'date' => $reg->session->date->toDateString(),
            'time' => ExamSession::BATCHES[$reg->session->batch]['time'],
            'venue' => $reg->session->venue,
            'permit' => $reg->permit_no,
        ];
    }

    private function documents(Application $application, string $stage): array
    {
        return $application->documents
            ->filter(fn (ApplicationDocument $d) => $d->requirement->stage === $stage)
            ->mapWithKeys(fn (ApplicationDocument $d) => [$d->requirement->key => [
                'name' => $d->original_name,
                'size' => $d->size,
                'type' => $d->mime,
                'status' => $d->status,
                'remark' => $d->remark,
            ]])
            ->all();
    }

    /**
     * { files, status } as applicant.js expects: status none → submitted →
     * cleared. A file the clinic sent back is left out of `files`, so its
     * card shows "Upload" again and the applicant can resubmit.
     */
    private function medical(Application $application): array
    {
        $all = $this->documents($application, Requirement::STAGE_MEDICAL);
        $files = array_filter($all, fn ($f) => $f['status'] !== ApplicationDocument::RESUBMIT);
        $required = Requirement::medical()->where('is_active', true)->count();
        $verified = count(array_filter($all, fn ($f) => $f['status'] === ApplicationDocument::VERIFIED));

        $status = match (true) {
            $required > 0 && $verified === $required => 'cleared',
            $application->medical_submitted_at !== null && count($files) === count($all) => 'submitted',
            default => 'none',
        };

        return ['files' => $files, 'status' => $status, 'detail' => $all];
    }
}
