<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Applicant registration and the application lifecycle (FR-01, FR-02, FR-05).
 */
class ApplicationService
{
    public function __construct(
        private ApplicationStatusService $status,
        private AuditLogger $audit,
    ) {}

    /** New applicant account: user + profile + draft application for the open period. */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $user->assignRole('applicant');

            $applicant = Applicant::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
            ]);

            $application = $this->start($applicant, $user);
            $this->audit->record('applicant.registered', 'Created an applicant account', $applicant, $application, actor: $user);

            return $user;
        });
    }

    public function start(Applicant $applicant, ?User $by = null): Application
    {
        $application = Application::create([
            'number' => 'pending-'.Str::uuid(),
            'applicant_id' => $applicant->id,
            'admission_period_id' => AdmissionPeriod::current()->id,
            'status' => ApplicationStatus::Draft,
            'form_progress' => ['step' => 1, 'completed' => []],
        ]);
        // The number comes from the row id, so it's unique without a race
        $application->update(['number' => sprintf('%s-%s-%05d', config('admission.number_prefix'), now()->year, $application->id)]);
        $this->status->recordCreated($application, $by);

        return $application;
    }

    /** The applicant's application for the open period, created if missing. */
    public function current(Applicant $applicant): Application
    {
        $period = AdmissionPeriod::current();

        return $applicant->applications()->where('admission_period_id', $period->id)->first()
            ?? $this->start($applicant, $applicant->user);
    }
}
