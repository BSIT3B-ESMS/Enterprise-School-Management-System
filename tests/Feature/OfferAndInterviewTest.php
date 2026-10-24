<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\Role;
use App\Models\College;
use App\Models\Program;
use App\Models\User;
use App\Notifications\AdmissionNotice;
use App\Notifications\TwoFactorCode;
use App\Services\ApplicationService;
use Database\Seeders\DemoStaffAccountsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsApplications;
use Tests\TestCase;

/** FR-09 (offer, slot acceptance, forfeit) and FR-17 (interview). */
class OfferAndInterviewTest extends TestCase
{
    use BuildsApplications;

    public function test_release_issues_an_offer_with_a_deadline(): void
    {
        $user = $this->released();
        $application = $this->app_($user);

        $this->assertSame(ApplicationStatus::Offered, $application->status);
        $this->assertSame('BSIT', $application->offeredProgram->code);
        $this->assertTrue($application->acceptance_deadline->isFuture());

        $deadline = $application->acceptance_deadline->setTimezone('Asia/Manila')->toDateString();
        $this->asUser($user)->getJson('/api/applicant/portal')
            ->assertJsonPath('data.phase', 'result')
            ->assertJsonPath('data.accepted', false)
            ->assertJsonPath('data.offer.deadline', $deadline)
            ->assertJsonFragment(['title' => 'Offer acceptance deadline', 'date' => $deadline]);

        // The letter is a PDF the applicant can download
        $this->asUser($user)->get('/api/applicant/acceptance-letter')->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_failed_result_is_final_and_gets_no_offer(): void
    {
        $user = $this->released('failed');
        $this->assertSame(ApplicationStatus::Failed, $this->app_($user)->status);
        $this->asUser($user)->postJson('/api/applicant/offer/accept')->assertStatus(422);
        $this->asUser($user)->get('/api/applicant/acceptance-letter')->assertStatus(422);
    }

    public function test_accepting_takes_the_slot_and_opens_medical(): void
    {
        $user = $this->released();
        $this->asUser($user)->postJson('/api/applicant/offer/accept')
            ->assertOk()->assertJsonPath('data.accepted', true)->assertJsonPath('data.status', 'for_medical');
        $this->asUser($user)->postJson('/api/applicant/offer/accept')->assertOk(); // double click

        $history = $this->app_($user)->statusHistory->pluck('to_status')->map->value->all();
        $this->assertSame(['offered', 'slot_accepted', 'for_medical'], array_slice($history, -3));
        $this->assertSame(1, Program::where('code', 'BSIT')->first()->filledSlots());
        $this->uploadDoc($user, 'medCert', 'medical')->assertCreated();
    }

    public function test_unaccepted_offer_is_forfeited_after_the_deadline(): void
    {
        Notification::fake();
        $user = $this->released();

        $this->travel(6)->days();
        Artisan::call('admission:deadlines', ['--only' => 'offer-reminders']);
        Notification::assertSentTo($user, AdmissionNotice::class, fn ($n) => $n->kind === 'offer_reminder');

        $this->travel(3)->days();
        Artisan::call('admission:deadlines', ['--only' => 'forfeit']);
        $this->assertSame(ApplicationStatus::Forfeited, $this->app_($user)->status);
        Notification::assertSentTo($user, AdmissionNotice::class, fn ($n) => $n->kind === 'offer_forfeited');

        $this->asUser($user)->postJson('/api/applicant/offer/accept')
            ->assertStatus(422)->assertJsonPath('message', 'The acceptance deadline passed and this offer was forfeited. Contact the admission office.');
        $this->assertSame(0, Program::where('code', 'BSIT')->first()->filledSlots());
    }

    /** NFR-04: with acceptance 2FA on, accepting a slot needs the emailed code */
    public function test_slot_acceptance_with_email_code(): void
    {
        config(['admission.applicant_acceptance_2fa' => true]);
        Notification::fake();
        $user = $this->released();

        $challenge = $this->asUser($user)->postJson('/api/applicant/offer/accept')
            ->assertStatus(202)->assertJsonPath('data.two_factor_required', true)->json('data.challenge');
        $code = null;
        Notification::assertSentTo($user, TwoFactorCode::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });

        $this->asUser($user)->postJson('/api/applicant/offer/accept', ['challenge' => $challenge, 'code' => '000000'])->assertStatus(422);
        $this->assertSame(ApplicationStatus::Offered, $this->app_($user)->status);
        $this->asUser($user)->postJson('/api/applicant/offer/accept', ['challenge' => $challenge, 'code' => $code])->assertOk();
        $this->assertSame(ApplicationStatus::ForMedical, $this->app_($user)->status);
    }

    public function test_interview_flow_with_dean_scoping(): void
    {
        $dean = $this->dean('CON');
        $otherDean = $this->dean('CCS');
        $user = $this->interviewee();
        $number = $this->number($user);
        $this->assertSame(ApplicationStatus::ForInterview, $this->app_($user)->status);
        $this->asUser($user)->postJson('/api/applicant/offer/accept')->assertStatus(422);

        // Admission personnel schedule and assign
        $schedule = $this->asUser($this->officer())->postJson('/api/admin/interview-schedules', [
            'program' => 'BSN', 'date' => now()->addDays(3)->toDateString(), 'time' => '9:00 AM', 'venue' => 'Nursing Bldg 105', 'capacity' => 1,
        ])->assertCreated()->json('data.id');
        $this->asUser($this->officer())->postJson('/api/admin/interview-schedules', ['program' => 'BSIT', 'date' => now()->addDay()->toDateString(), 'venue' => 'X', 'capacity' => 5])
            ->assertStatus(422);
        $this->asUser($this->officer())->postJson("/api/admin/applicants/{$number}/interview", ['scheduleId' => $schedule])->assertOk();

        // Capacity 1: a second interviewee doesn't fit
        $second = $this->interviewee('second@example.com');
        $this->asUser($this->officer())->postJson("/api/admin/applicants/{$this->number($second)}/interview", ['scheduleId' => $schedule])
            ->assertStatus(422)->assertJsonPath('message', 'That interview schedule is full. Pick another one.');

        // Dean scoping: the CCS dean sees nothing and can't record; admission personnel can't record
        $this->asUser($otherDean)->getJson('/api/dean/interviews')->assertOk()->assertJsonPath('data.schedules', []);
        $this->asUser($otherDean)->postJson("/api/dean/applicants/{$number}/interview", ['program' => 'BSN', 'grade' => 90, 'outcome' => 'passed'])->assertForbidden();
        $this->asUser($this->officer())->postJson("/api/dean/applicants/{$number}/interview", ['program' => 'BSN', 'grade' => 90, 'outcome' => 'passed'])->assertForbidden();
        $this->asUser($dean)->getJson('/api/admin/state')->assertForbidden();

        $this->asUser($dean)->getJson('/api/dean/interviews')->assertOk()->assertJsonPath('data.schedules.0.applicants.0.id', $number);
        $this->asUser($dean)->postJson("/api/dean/applicants/{$number}/interview", ['program' => 'BSN', 'grade' => 91, 'outcome' => 'passed'])
            ->assertOk()->assertJsonPath('data.status', 'offered');
        $this->asUser($dean)->postJson("/api/dean/applicants/{$number}/interview", ['program' => 'BSN', 'grade' => 50, 'outcome' => 'failed'])->assertStatus(422);

        // The applicant sees the outcome, never the grade
        $portal = $this->asUser($user)->getJson('/api/applicant/portal')->assertJsonPath('data.interviews.0.outcome', 'passed');
        $this->assertStringNotContainsString('grade', $portal->getContent());
        $this->assertStringNotContainsString('91', json_encode($portal->json('data.interviews')));
        // Personnel see the grade
        $this->asUser($this->officer())->getJson('/api/admin/state')->assertJsonFragment(['outcome' => 'passed', 'grade' => 91]);
    }

    /** A rejection by the first choice's dean moves on to the next choice (no interview needed there) */
    public function test_rejected_by_the_first_choice_gets_the_next_one(): void
    {
        $dean = $this->dean('CON');
        $user = $this->interviewee('jordan@example.com', ['program2' => 'BS Computer Science']);
        $number = $this->number($user);
        $this->scheduleAndAssign($number, 'BSN');

        $this->asUser($dean)->postJson("/api/dean/applicants/{$number}/interview", ['program' => 'BSN', 'outcome' => 'failed', 'remark' => 'Not a fit'])
            ->assertOk()->assertJsonPath('data.status', 'offered');
        $this->assertSame('BSCS', $this->app_($user)->offeredProgram->code);
        $this->asUser($dean)->getJson('/api/dean/interviews')->assertJsonPath('data.schedules.0.applicants.0.remark', 'Not a fit');
    }

    /** Interviews run one choice at a time: the 2nd choice is scheduled only after the 1st dean rejects */
    public function test_interviews_run_one_choice_at_a_time(): void
    {
        Program::where('code', 'BSCS')->update(['requires_interview' => true]);
        $nursing = $this->dean('CON');
        $ccs = $this->dean('CCS');
        $user = $this->interviewee('jordan@example.com', ['program2' => 'BS Computer Science']);
        $number = $this->number($user);
        $this->assertSame(ApplicationStatus::ForInterview, $this->app_($user)->status);

        // Only the first choice waits for a schedule; the 2nd can't be booked yet
        $ccsSchedule = $this->asUser($this->officer())->postJson('/api/admin/interview-schedules', [
            'program' => 'BSCS', 'date' => now()->addDays(4)->toDateString(), 'time' => '1:00 PM', 'venue' => '3rd Floor, CCS Faculty', 'capacity' => 10,
        ])->assertCreated()->json('data.id');
        $this->asUser($this->officer())->postJson("/api/admin/applicants/{$number}/interview", ['scheduleId' => $ccsSchedule])->assertUnprocessable();
        $this->assertSame(['BSN'], array_column($this->asUser($this->officer())->getJson('/api/admin/interviews')->json('data.waiting'), 'program'));

        $this->scheduleAndAssign($number, 'BSN', '3rd Floor, CON Faculty');
        $this->assertSame([], $this->asUser($this->officer())->getJson('/api/admin/interviews')->json('data.waiting'));
        $portal = $this->asUser($user)->getJson('/api/applicant/portal');
        $portal->assertJsonPath('data.interviews.0.program', 'BS Nursing')->assertJsonPath('data.interviews.0.venue', '3rd Floor, CON Faculty')
            ->assertJsonCount(1, 'data.interviews');

        // Each dean decides only their own program
        $this->asUser($ccs)->postJson("/api/dean/applicants/{$number}/interview", ['program' => 'BSN', 'outcome' => 'failed'])->assertForbidden();
        $this->asUser($nursing)->postJson("/api/dean/applicants/{$number}/interview", ['program' => 'BSN', 'outcome' => 'failed'])
            ->assertOk()->assertJsonPath('data.status', 'for_interview');

        // The rejection moves the applicant on to the 2nd choice's interview
        $this->asUser($user)->getJson('/api/applicant/portal')->assertJsonCount(2, 'data.interviews')->assertJsonPath('data.interviews.1.program', 'BS Computer Science');
        $this->assertSame(['BSCS'], array_column($this->asUser($this->officer())->getJson('/api/admin/interviews')->json('data.waiting'), 'program'));
        $this->asUser($this->officer())->postJson("/api/admin/applicants/{$number}/interview", ['scheduleId' => $ccsSchedule])->assertOk();
        $this->asUser($this->dean('CCS'))->postJson("/api/dean/applicants/{$number}/interview", ['program' => 'BSCS', 'outcome' => 'passed'])
            ->assertOk()->assertJsonPath('data.status', 'offered');
        $this->assertSame('BSCS', $this->app_($user)->offeredProgram->code);
    }

    public function test_rejected_by_every_choice_is_final(): void
    {
        Program::whereIn('code', ['BSCS', 'ABPSY'])->update(['requires_interview' => true]);
        $user = $this->interviewee();   // BSN, BSCS, ABPSY
        $number = $this->number($user);

        $this->scheduleAndAssign($number, 'BSN');
        $this->asUser($this->dean('CON'))->postJson("/api/dean/applicants/{$number}/interview", ['program' => 'BSN', 'outcome' => 'failed'])->assertOk();
        $this->scheduleAndAssign($number, 'BSCS');
        $this->asUser($this->dean('CCS'))->postJson("/api/dean/applicants/{$number}/interview", ['program' => 'BSCS', 'outcome' => 'failed'])->assertOk();
        $this->assertSame(ApplicationStatus::ForInterview, $this->app_($user)->status);
        $this->scheduleAndAssign($number, 'ABPSY');
        $this->asUser($this->admissionHead())->postJson("/api/admin/applicants/{$number}/evaluation", ['program' => 'ABPSY', 'outcome' => 'failed'])->assertOk();
        $this->assertSame(ApplicationStatus::InterviewFailed, $this->app_($user)->status);
        $this->asUser($user)->postJson('/api/applicant/offer/accept')->assertStatus(422);
    }

    /** The Admin runs the whole system: records an evaluation for any college; personnel can't */
    public function test_admin_can_record_an_evaluation(): void
    {
        $user = $this->interviewee();
        $number = $this->number($user);
        $schedule = $this->asUser($this->officer())->postJson('/api/admin/interview-schedules', [
            'program' => 'BSN', 'date' => now()->addDays(3)->toDateString(), 'venue' => 'Room 1', 'capacity' => 5,
        ])->json('data.id');
        $this->asUser($this->officer())->postJson("/api/admin/applicants/{$number}/interview", ['scheduleId' => $schedule])->assertOk();

        $this->asUser($this->officer())->postJson("/api/admin/applicants/{$number}/evaluation", ['program' => 'BSN', 'grade' => 88, 'outcome' => 'passed'])->assertForbidden();
        $this->asUser($this->admissionHead())->postJson("/api/admin/applicants/{$number}/evaluation", ['program' => 'BSN', 'grade' => 88, 'outcome' => 'passed'])
            ->assertOk()->assertJsonPath('data.status', 'offered');
        $this->assertSame('BSN', $this->app_($user)->offeredProgram->code);
    }

    /** College Dean "Applicant List": only applicants who chose one of the dean's college programs */
    public function test_dean_applicant_list_is_scoped_to_the_college(): void
    {
        $ccs = $this->dean('CCS');
        $con = $this->dean('CON');
        $it = $this->submitted();   // BSIT, BSCS, ABPSY
        $nurse = $this->makeApplicant(true, 'nurse@example.com');
        app(ApplicationService::class)->saveForm($this->applicationOf($nurse), ['program1' => 'BS Nursing', 'program2' => 'AB Psychology', 'program3' => 'BS Mathematics'], $nurse);
        foreach (app(ApplicationService::class)->requiredDocuments($this->applicationOf($nurse)) as $r) {
            $this->uploadDoc($nurse, $r->key)->assertCreated();
        }
        $this->asUser($nurse)->postJson('/api/applicant/submit')->assertOk();

        $list = $this->asUser($ccs)->getJson('/api/dean/applicants')->assertOk();
        $this->assertSame([$this->number($it)], array_column($list->json('data.applicants'), 'id'));
        $this->assertSame('CCS', $list->json('data.college.code'));
        $this->assertSame(['BSCS', 'BSIT'], array_column($list->json('data.programs'), 'code'));
        $this->assertStringNotContainsString('score', $list->getContent());

        $this->assertSame([$this->number($nurse)], array_column($this->asUser($con)->getJson('/api/dean/applicants')->json('data.applicants'), 'id'));
        $this->asUser($this->officer())->getJson('/api/dean/applicants')->assertForbidden();
    }

    /** Admin-only: dates of admission and the office audit log (use-case diagrams) */
    public function test_admin_only_screens(): void
    {
        $this->asUser($this->officer())->patchJson('/api/admin/admission-period', ['deadline' => '2026-12-01'])->assertForbidden();
        $this->asUser($this->admissionHead())->patchJson('/api/admin/admission-period', ['deadline' => '2026-12-01', 'examStartsOn' => '2026-12-07'])->assertOk();
        // Slot setup and the interview switch stay with admission personnel
        $this->asUser($this->officer())->patchJson('/api/admin/programs/BSIT', ['slots' => 100, 'requiresInterview' => true])->assertOk()->assertJsonPath('data.requiresInterview', true);

        $this->asUser($this->officer())->getJson('/api/admin/state')->assertJsonPath('data.audit', []);
        $this->assertNotEmpty($this->asUser($this->admissionHead())->getJson('/api/admin/state')->json('data.audit'));
    }

    /** Demo College Dean per college (dean.<code>@plpasig.edu.ph / dean1234), linked to it */
    public function test_demo_dean_accounts(): void
    {
        $this->seed(DemoStaffAccountsSeeder::class);
        $this->postJson('/api/auth/login', ['email' => 'dean.ccs@plpasig.edu.ph', 'password' => 'dean1234', 'portal' => 'office'])
            ->assertOk()->assertJsonPath('data.user.role', 'college_dean');
        $this->assertSame('dean.ccs@plpasig.edu.ph', College::where('code', 'CCS')->first()->dean->email);
        $this->assertSame(College::count(), User::role('college_dean')->count());
    }

    /** A pass whose first choice is BS Nursing (requires an interview). */
    private function interviewee(string $email = 'jordan@example.com', array $more = []): User
    {
        $user = $this->approvedFor('BS Nursing', $email, $more);
        $number = $this->number($user);
        $this->asUser($this->officer())->patchJson("/api/admin/applicants/{$number}/exam", ['permitChecked' => true, 'formChecked' => true, 'attendance' => 'present'])->assertOk();
        $this->asUser($this->officer())->postJson("/api/admin/applicants/{$number}/result", ['score' => 85, 'decision' => 'passed'])->assertOk();
        $this->asUser($this->admissionHead())->postJson('/api/admin/results/release', ['ids' => [$number]])->assertOk();

        return $user;
    }

    private function approvedFor(string $program, string $email, array $more = []): User
    {
        $user = $this->makeApplicant(true, $email);
        app(ApplicationService::class)->saveForm($this->applicationOf($user), ['program1' => $program] + $more, $user);
        foreach (app(ApplicationService::class)->requiredDocuments($this->applicationOf($user)) as $r) {
            $this->uploadDoc($user, $r->key)->assertCreated();
        }
        $this->asUser($user)->postJson('/api/applicant/submit')->assertOk();
        $number = $this->number($user);
        $this->asUser($this->officer())->postJson("/api/admin/applicants/{$number}/documents/verify-pending")->assertOk();
        $session = $this->sharedSession ??= $this->examSession();
        $this->asUser($this->officer())->postJson("/api/admin/applicants/{$number}/approve", ['sessionId' => 'S'.$session->id])->assertOk();

        return $user;
    }

    /** A schedule for the program (capacity 10) and the applicant on it. */
    private function scheduleAndAssign(string $number, string $program, string $venue = 'Room 1'): void
    {
        $schedule = $this->asUser($this->officer())->postJson('/api/admin/interview-schedules', [
            'program' => $program, 'date' => now()->addDays(3)->toDateString(), 'time' => '9:00 AM', 'venue' => $venue, 'capacity' => 10,
        ])->assertCreated()->json('data.id');
        $this->asUser($this->officer())->postJson("/api/admin/applicants/{$number}/interview", ['scheduleId' => $schedule])->assertOk();
    }

    private function dean(string $college): User
    {
        $dean = $this->makeUser(Role::CollegeDean, ['name' => "Dean {$college}"]);
        College::where('code', $college)->update(['dean_user_id' => $dean->id]);

        return $dean;
    }
}
