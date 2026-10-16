<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\Role;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ExamResult;
use App\Models\User;
use App\Services\ApplicationService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Phase 4: document upload, review and resubmission (FR-03, FR-04), medical (FR-13). */
class DocumentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function pdf(string $name = 'file.pdf', int $kb = 200): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'application/pdf');
    }

    private function upload(User $user, string $key, ?UploadedFile $file = null, string $stage = 'documents')
    {
        return $this->asUser($user)->post("/api/applicant/{$stage}/{$key}", ['file' => $file ?? ($key === 'photo' ? UploadedFile::fake()->image('photo.jpg') : $this->pdf())], ['Accept' => 'application/json']);
    }

    /** A filled, submitted application with every required document uploaded. */
    private function submittedApplicant(): User
    {
        $user = $this->makeApplicant(true);
        foreach (app(ApplicationService::class)->requiredDocuments($this->applicationOf($user)) as $r) {
            $this->upload($user, $r->key)->assertCreated();
        }
        $this->asUser($user)->postJson('/api/applicant/submit')->assertOk();

        return $user;
    }

    /** FR-03 */
    public function test_applicant_uploads_to_private_storage(): void
    {
        $user = $this->makeApplicant(true);

        $this->upload($user, 'psa', $this->pdf('psa_birth_certificate.pdf'))
            ->assertCreated()
            ->assertJsonPath('data.documents.psa.name', 'psa_birth_certificate.pdf')
            ->assertJsonPath('data.documents.psa.status', 'pending')
            ->assertJsonMissingPath('data.documents.psa.storage_ref');

        $doc = ApplicationDocument::first();
        Storage::disk('local')->assertExists($doc->storage_ref);
        $this->assertStringStartsWith('documents/'.$this->applicationOf($user)->number.'/', $doc->storage_ref);
        $this->assertStringNotContainsString('psa_birth', $doc->storage_ref); // random name
    }

    /** Decision T8: type and 5 MB limit */
    public function test_upload_rules(): void
    {
        $user = $this->makeApplicant(true);

        $this->upload($user, 'psa', $this->pdf('big.pdf', 5121))->assertUnprocessable()
            ->assertJsonPath('errors.file.0', 'This file is over 5 MB. Compress it or choose a smaller file.');
        $this->upload($user, 'photo', $this->pdf())->assertUnprocessable()
            ->assertJsonPath('errors.file.0', 'Choose a JPG or PNG photo.');
        $this->upload($user, 'psa', UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload'))->assertUnprocessable();
        $this->upload($user, 'nope')->assertUnprocessable();
        // Guardianship affidavit applies only to applicants with a guardian
        $this->upload($user, 'affidavit')->assertUnprocessable();
        // Medical opens only after a released pass
        $this->upload($user, 'medCert', null, 'medical')->assertUnprocessable();
    }

    public function test_replace_keeps_versions_and_remove_in_draft(): void
    {
        $user = $this->makeApplicant(true);
        $this->upload($user, 'psa')->assertCreated();
        $this->upload($user, 'psa', $this->pdf('second.pdf'))->assertCreated();

        $this->assertSame(2, ApplicationDocument::count());
        $this->assertSame(2, ApplicationDocument::where('is_current', true)->first()->version);

        $this->asUser($user)->deleteJson('/api/applicant/documents/psa')->assertOk()->assertJsonMissingPath('data.documents.psa');
    }

    public function test_documents_lock_after_submission(): void
    {
        $user = $this->submittedApplicant();

        $this->upload($user, 'psa')->assertUnprocessable();
        $this->asUser($user)->deleteJson('/api/applicant/documents/psa')->assertUnprocessable();
    }

    /** FR-04: full review → resubmit → review loop */
    public function test_review_and_resubmission_loop(): void
    {
        $user = $this->submittedApplicant();
        $application = $this->applicationOf($user);
        $staff = $this->makeUser(Role::Admission, ['name' => 'Maria Santos']);
        $url = "/api/admin/applicants/{$application->number}/documents";

        // Remark is required to send a file back
        $this->asUser($staff)->patchJson("{$url}/psa", ['status' => 'resubmit'])->assertUnprocessable();

        $this->asUser($staff)->patchJson("{$url}/psa", ['status' => 'resubmit', 'remark' => 'Image is blurry.'])
            ->assertOk()
            ->assertJsonPath('data.docs.psa.status', 'resubmit')
            ->assertJsonPath('data.docs.psa.remark', 'Image is blurry.')
            ->assertJsonPath('data.docs.psa.reviewedBy', 'Maria Santos');
        $this->assertSame(ApplicationStatus::Resubmit, $application->fresh()->status);

        $this->asUser($staff)->postJson("{$url}/verify-pending")->assertOk()->assertJsonPath('verified', 4);

        // The applicant may replace only the file that was sent back
        $this->asUser($user)->getJson('/api/applicant/portal')->assertJsonPath('data.phase', 'draft');
        $this->upload($user, 'photo')->assertUnprocessable();
        $this->upload($user, 'psa', $this->pdf('clear.pdf'))->assertCreated();
        $this->asUser($user)->postJson('/api/applicant/submit')->assertOk()->assertJsonPath('data.phase', 'review');
        $this->assertSame(ApplicationStatus::Submitted, $application->fresh()->status);

        $this->asUser($staff)->patchJson("{$url}/psa", ['status' => 'verified'])->assertOk()->assertJsonPath('data.docs.psa.status', 'verified');
        $this->assertDatabaseHas('audit_logs', ['description' => 'Marked "PSA Birth Certificate" as requires resubmission: Image is blurry.']);
        $this->assertDatabaseHas('audit_logs', ['description' => 'Resubmitted the requested documents']);
    }

    /** NFR-01: medical files are confidential to the clinic */
    public function test_reviewer_roles_and_file_access(): void
    {
        $user = $this->submittedApplicant();
        $number = $this->applicationOf($user)->number;
        $clinic = $this->makeUser(Role::Clinic);
        $staff = $this->makeUser(Role::Admission);

        $this->asUser($clinic)->patchJson("/api/admin/applicants/{$number}/documents/psa", ['status' => 'verified'])->assertForbidden();
        $this->asUser($clinic)->get("/api/admin/applicants/{$number}/documents/psa/file")->assertForbidden();
        $this->asUser($staff)->get("/api/admin/applicants/{$number}/documents/psa/file")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->asUser($user)->get('/api/applicant/documents/psa/file')->assertOk();
        $this->asUser($user)->getJson('/api/admin/state')->assertForbidden();
    }

    /** FR-13, decision T2 */
    public function test_medical_flow(): void
    {
        $user = $this->submittedApplicant();
        $application = $this->applicationOf($user);
        $this->releasePass($application);
        $clinic = $this->makeUser(Role::Clinic, ['name' => 'Dr. L. Bautista']);
        $admission = $this->makeUser(Role::Admission);

        foreach (['medCert', 'xray', 'labs'] as $key) {
            $this->upload($user, $key, null, 'medical')->assertCreated();
        }
        $this->asUser($user)->postJson('/api/applicant/medical/submit')->assertOk()->assertJsonPath('data.medical.status', 'submitted');

        $url = "/api/admin/applicants/{$application->number}/medical";
        $this->asUser($admission)->patchJson("{$url}/xray", ['status' => 'verified'])->assertForbidden();
        $this->asUser($clinic)->get("/api/admin/applicants/{$application->number}/documents/xray/file")->assertOk();
        $this->asUser($admission)->get("/api/admin/applicants/{$application->number}/documents/xray/file")->assertForbidden();

        $this->asUser($clinic)->patchJson("{$url}/xray", ['status' => 'resubmit', 'remark' => 'Result is older than 3 months.'])
            ->assertOk()->assertJsonPath('data.medical.files.xray.status', 'resubmit');
        // The sent-back file disappears from the portal so the card asks for an upload again
        $this->asUser($user)->getJson('/api/applicant/portal')
            ->assertJsonPath('data.medical.status', 'none')
            ->assertJsonMissingPath('data.medical.files.xray');

        $this->upload($user, 'xray', null, 'medical')->assertCreated();
        $this->asUser($user)->postJson('/api/applicant/medical/submit')->assertOk();
        $this->asUser($clinic)->postJson("{$url}/verify-pending")->assertOk()->assertJsonPath('verified', 3);

        $this->asUser($user)->getJson('/api/applicant/portal')->assertJsonPath('data.medical.status', 'cleared');
        $this->assertDatabaseHas('audit_logs', ['description' => 'Medically cleared · admission complete', 'actor_name' => 'Dr. L. Bautista']);
    }

    /** Decision T5: documents go to the DMS with the API key when it's switched on */
    public function test_dms_store(): void
    {
        config(['admission.documents.driver' => 'dms', 'admission.documents.dms.base_url' => 'https://dms.test/api', 'admission.documents.dms.api_key' => 'k-123']);
        Http::fake(['dms.test/api/documents' => Http::response(['id' => 'DMS-778'], 201)]);
        $user = $this->makeApplicant(true);

        $this->upload($user, 'psa')->assertCreated();

        $doc = ApplicationDocument::first();
        $this->assertSame(['dms', 'DMS-778'], [$doc->storage_driver, $doc->storage_ref]);
        Http::assertSent(fn (HttpRequest $r) => $r->hasHeader('X-API-Key', 'k-123') && $r->hasHeader('Idempotency-Key'));
    }

    /** Stand-in for Phases 6–7: a released passing result. */
    private function releasePass(Application $application): void
    {
        $staff = $this->makeUser(Role::Admission);
        $application->forceFill(['status' => ApplicationStatus::Passed])->save();
        ExamResult::create(['application_id' => $application->id, 'score' => 80, 'decision' => 'passed', 'recorded_by' => $staff->id, 'recorded_at' => now(), 'is_released' => true, 'released_by' => $staff->id, 'released_at' => now()]);
    }
}
