<?php

namespace Tests\Concerns;

use App\Enums\Role;
use App\Models\AdmissionPeriod;
use App\Models\Application;
use App\Models\ExamSession;
use App\Models\User;
use App\Services\ApplicationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Moves an applicant through the real API to a given stage, so later-phase
 * tests start from data the earlier phases produced.
 */
trait BuildsApplications
{
    protected ?User $officer = null;

    protected function officer(): User
    {
        return $this->officer ??= $this->makeUser(Role::Admission, ['name' => 'Maria Santos']);
    }

    protected function uploadDoc(User $user, string $key, string $stage = 'documents')
    {
        $file = $key === 'photo' ? $this->fakePhoto() : UploadedFile::fake()->create("{$key}.pdf", 200, 'application/pdf');

        return $this->asUser($user)->post("/api/applicant/{$stage}/{$key}", ['file' => $file], ['Accept' => 'application/json']);
    }

    protected function submitted(string $email = 'jordan@example.com'): User
    {
        Storage::fake('local');
        $user = $this->makeApplicant(true, $email);
        foreach (app(ApplicationService::class)->requiredDocuments($this->applicationOf($user)) as $r) {
            $this->uploadDoc($user, $r->key)->assertCreated();
        }
        $this->asUser($user)->postJson('/api/applicant/submit')->assertOk();

        return $user;
    }

    protected function examSession(int $capacity = 30, string $date = '2026-12-05', string $batch = 'A'): ExamSession
    {
        return ExamSession::create(['admission_period_id' => AdmissionPeriod::current()->id, 'date' => $date, 'batch' => $batch, 'venue' => 'Room 301', 'capacity' => $capacity]);
    }

    /** Verified and approved for an exam session. */
    protected function approved(string $email = 'jordan@example.com', ?ExamSession $session = null): User
    {
        $user = $this->submitted($email);
        $number = $this->applicationOf($user)->number;
        $session ??= $this->examSession();
        $this->asUser($this->officer())->postJson("/api/admin/applicants/{$number}/documents/verify-pending")->assertOk();
        $this->asUser($this->officer())->postJson("/api/admin/applicants/{$number}/approve", ['sessionId' => 'S'.$session->id])->assertOk();

        return $user;
    }

    /** Took the exam, result recorded (not released). */
    protected function examined(string $decision = 'passed', int $score = 82, string $email = 'jordan@example.com'): User
    {
        $user = $this->approved($email);
        $number = $this->applicationOf($user)->number;
        $this->asUser($this->officer())->patchJson("/api/admin/applicants/{$number}/exam", ['permitChecked' => true, 'formChecked' => true, 'attendance' => 'present'])->assertOk();
        $this->asUser($this->officer())->postJson("/api/admin/applicants/{$number}/result", compact('score', 'decision'))->assertOk();

        return $user;
    }

    protected function number(User $user): string
    {
        return $this->applicationOf($user)->number;
    }

    protected function app_(User $user): Application
    {
        return $this->applicationOf($user)->fresh();
    }
}
