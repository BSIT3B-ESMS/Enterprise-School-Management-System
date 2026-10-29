<?php

namespace App\Actions\Recruitment;

use App\Enums\ApplicationStatus;
use App\Enums\AuditAction;
use App\Models\Application;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use App\Services\DocumentStore;
use App\Services\Notifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SubmitApplication
{
    public function __construct(
        private AuditLogger $audit,
        private ApplicationTimeline $timeline,
        private DocumentStore $documents,
        private Notifier $notifier,
    ) {}

    /**
     * Files an application for an open position. The position is automatic (it is the one chosen); the resume (education,
     * work history, and an uploaded file) and the optional portfolio belong to this application. One person cannot hold two
     * active applications for the same position.
     *
     * @param  array{education: list<array<string, string>>, work_history: list<array<string, string>>, portfolio_url?: ?string, cover_note?: ?string}  $data
     *
     * @throws RecruitmentException
     */
    public function handle(User $applicant, JobTitle $position, array $data, UploadedFile $resume, ?UploadedFile $portfolio = null): Application
    {
        if (! $applicant->applicantProfile?->isComplete()) {
            throw new RecruitmentException('Complete your profile (birthdate, sex, civil status, and contact details) before you apply.');
        }

        if (! $position->is_active || ! $position->is_published || $position->openings < 1) {
            throw new RecruitmentException('This position is not open for applications.');
        }

        $alreadyApplied = Application::query()
            ->where('user_id', $applicant->id)
            ->where('job_title_id', $position->id)
            ->whereIn('status', [ApplicationStatus::Applied, ApplicationStatus::DemoInterview, ApplicationStatus::Offered, ApplicationStatus::Hired])
            ->exists();

        if ($alreadyApplied) {
            throw new RecruitmentException('You already have an application for this position.');
        }

        return DB::transaction(function () use ($applicant, $position, $data, $resume, $portfolio): Application {
            $application = Application::query()->create([
                'user_id' => $applicant->id,
                'job_title_id' => $position->id,
                'status' => ApplicationStatus::Applied,
                'applied_on' => now()->toDateString(),
                'education' => $data['education'],
                'work_history' => $data['work_history'],
                'portfolio_url' => $data['portfolio_url'] ?? null,
                'cover_note' => $data['cover_note'] ?? null,
                'status_changed_at' => now(),
            ]);

            $this->documents->put($application, 'resume', $resume, $applicant);

            if ($portfolio) {
                $this->documents->put($application, 'portfolio', $portfolio, $applicant);
            }

            $this->timeline->add($application, 'applied', $applicant);
            $this->audit->log(AuditAction::Create, $application, ['job_title_id' => $position->id], $applicant);

            $this->notifier->notify(
                $this->notifier->hrUsers(),
                'New application',
                "{$applicant->applicantProfile->full_name} applied for {$position->title}.",
                route('recruitment.show', $application),
            );

            return $application;
        });
    }
}
