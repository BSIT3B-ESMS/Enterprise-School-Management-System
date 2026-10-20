<?php

namespace Database\Seeders;

use App\Actions\Recruitment\CancelApplication;
use App\Actions\Recruitment\HireApplicant;
use App\Actions\Recruitment\IssueOffer;
use App\Actions\Recruitment\RejectApplication;
use App\Actions\Recruitment\RespondToOffer;
use App\Actions\Recruitment\ScheduleDemoInterview;
use App\Enums\ApplicationStatus;
use App\Enums\EmploymentType;
use App\Enums\InterviewMode;
use App\Enums\InterviewStatus;
use App\Enums\Role;
use App\Models\Application;
use App\Models\Benefit;
use App\Models\Interview;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\ApplicationTimeline;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Fictional applicants with working accounts, spread over every stage of the pipeline so the Recruitment, Calendar, Dashboard, and the
 * applicant pages have something to show. Every account signs in with the same password (see PASSWORD). The emails use example.test,
 * a reserved domain, so a message sent to one can never reach a real person.
 */
class ApplicantSeeder extends Seeder
{
    public const PASSWORD = 'Applicant#2026';

    /**
     * first, middle, last, email local part, scenario.
     *
     * @var list<array{0: string, 1: ?string, 2: string, 3: string, 4: string}>
     */
    private const PEOPLE = [
        ['Maricel', 'Dela Paz', 'Soriano', 'maricel.soriano', 'applied'],
        ['Jericho', null, 'Villanueva', 'jericho.villanueva', 'applied'],
        ['Annalyn', 'Reyes', 'Castillo', 'annalyn.castillo', 'applied'],
        ['Francis', 'Lim', 'Ocampo', 'francis.ocampo', 'applied'],
        ['Rhodora', null, 'Mendoza', 'rhodora.mendoza', 'demo_upcoming'],
        ['Gilbert', 'Cruz', 'Pascual', 'gilbert.pascual', 'demo_upcoming_requested'],
        ['Liza', null, 'Navarro', 'liza.navarro', 'demo_soon'],
        ['Armando', 'Santos', 'Dizon', 'armando.dizon', 'demo_done'],
        ['Cristina', null, 'Bautista', 'cristina.bautista', 'offered'],
        ['Vicente', 'Tan', 'Rivera', 'vicente.rivera', 'accepted'],
        ['Norma', null, 'Aguilar', 'norma.aguilar', 'rejected'],
        ['Edgar', 'Lopez', 'Fernandez', 'edgar.fernandez', 'cancelled'],
        ['Teresita', 'Gomez', 'Padilla', 'teresita.padilla', 'hired'],
        ['Roland', null, 'Cabrera', 'roland.cabrera', 'no_application'],
    ];

    public function run(): void
    {
        $admin = User::query()->where('role', Role::HrAdmin)->orderBy('id')->first();
        $hr = User::query()->whereIn('role', [Role::HrAdmin, Role::HrStaff])->where('is_active', true)->orderBy('id')->get();
        $positions = JobTitle::query()->openForApplications()->orderBy('id')->get();

        if (! $admin || $positions->isEmpty()) {
            return;
        }

        fake()->seed(20261021);

        foreach (self::PEOPLE as $index => [$first, $middle, $last, $local, $scenario]) {
            $user = $this->createApplicant($first, $middle, $last, $local, $scenario !== 'no_application' || true);

            if ($scenario === 'no_application') {
                $user->applicantProfile->update(['birthdate' => null, 'sex' => null, 'marital_status' => null, 'phone' => null, 'address' => null]);

                continue;
            }

            $position = $positions[$index % $positions->count()];
            $application = $this->createApplication($user, $position, $scenario);
            $this->advance($application, $scenario, $admin, $hr, $index);
        }
    }

    private function createApplicant(string $first, ?string $middle, string $last, string $local, bool $complete): User
    {
        $user = new User(['name' => implode(' ', array_filter([$first, $middle, $last])), 'email' => $local.'@example.test', 'password' => self::PASSWORD]);
        $user->forceFill(['role' => Role::Applicant, 'is_active' => true, 'password_changed_at' => now(), 'privacy_acknowledged_at' => now()])->save();

        $user->applicantProfile()->create([
            'first_name' => $first,
            'middle_name' => $middle,
            'last_name' => $last,
            'birthdate' => fake()->dateTimeBetween('-42 years', '-23 years')->format('Y-m-d'),
            'sex' => SyntheticProfile::sexFor($first),
            'marital_status' => fake()->randomElement(['Single', 'Married', 'Single']),
            'phone' => '09'.fake()->numerify('#########'),
            'address' => fake()->numberBetween(10, 400).' '.fake()->randomElement(['Kapitolyo', 'Caniogan', 'Santolan', 'Manggahan', 'Rosario']).', Pasig City',
        ]);

        return $user;
    }

    private function createApplication(User $user, JobTitle $position, string $scenario): Application
    {
        $appliedOn = now()->subDays(fake()->numberBetween(2, 14))->toDateString();

        $application = Application::query()->create([
            'user_id' => $user->id,
            'job_title_id' => $position->id,
            'status' => ApplicationStatus::Applied,
            'applied_on' => $appliedOn,
            'education' => [
                ['level' => 'Master', 'school' => 'Polytechnic University of the Philippines', 'course' => 'MA in Education', 'year_graduated' => (string) fake()->numberBetween(2012, 2022)],
                ['level' => 'Bachelor', 'school' => 'Pamantasan ng Lungsod ng Pasig', 'course' => 'BS in Education', 'year_graduated' => (string) fake()->numberBetween(2005, 2012)],
            ],
            'work_history' => [
                ['employer' => 'Sample Elementary School', 'position' => 'Teacher I', 'from' => '2015-06', 'to' => '2020-03'],
                ['employer' => 'Sample National High School', 'position' => 'Teacher II', 'from' => '2020-06', 'to' => 'Present'],
            ],
            'cover_note' => 'I am interested in teaching at PLP.',
            'status_changed_at' => $appliedOn,
        ]);

        $name = $user->applicantProfile->full_name;
        $this->document($application, 'resume', "Resume - {$name}", $name.'-resume.pdf', $user);

        if ($scenario !== 'applied') {
            $this->document($application, 'portfolio', "Portfolio - {$name}", $name.'-portfolio.pdf', $user);
        }

        app(ApplicationTimeline::class)->add($application, 'applied', $user);
        $application->events()->latest('id')->first()?->forceFill([])->saveQuietly();

        return $application;
    }

    private function advance(Application $application, string $scenario, User $admin, $hr, int $index): void
    {
        $interviewer = $hr[$index % $hr->count()];

        switch ($scenario) {
            case 'demo_upcoming':
            case 'demo_upcoming_requested':
                $start = now()->addDays($scenario === 'demo_upcoming' ? 4 : 7)->setTime(10 + ($index % 3), 0);
                $interview = $this->schedule($application, $admin, $interviewer, $start);

                if ($scenario === 'demo_upcoming_requested') {
                    $interview->forceFill([
                        'reschedule_requested_at' => now()->subHours(3),
                        'reschedule_reason' => 'I have a class to handle that morning.',
                        'reschedule_available_days' => 'Any afternoon next week',
                    ])->save();
                }
                break;

            case 'demo_soon':
                $this->schedule($application, $admin, $interviewer, now()->hour < 15 && now()->hour >= 8 ? now()->setTime(16, 0) : now()->addDay()->setTime(8, 0));
                break;

            case 'demo_done':
                $this->completedInterview($application, $admin, $interviewer);
                break;

            case 'offered':
            case 'accepted':
            case 'hired':
                $this->completedInterview($application, $admin, $interviewer);
                $this->offer($application, $admin);

                if ($scenario !== 'offered') {
                    app(RespondToOffer::class)->accept($application->applicant, $application->fresh()->pendingOffer(), $application->applicant->applicantProfile->full_name, '127.0.0.1');
                }

                if ($scenario === 'hired') {
                    app(HireApplicant::class)->handle($admin, $application->fresh());
                }
                break;

            case 'rejected':
                app(RejectApplication::class)->handle($admin, $application, 'The qualifications do not match the post.');
                break;

            case 'cancelled':
                app(CancelApplication::class)->handle($application->applicant, $application, 'I accepted another offer.');
                break;
        }
    }

    private function schedule(Application $application, User $admin, User $interviewer, $start): Interview
    {
        return app(ScheduleDemoInterview::class)->handle(
            $admin,
            $application,
            $interviewer,
            $start,
            $start->copy()->addHour(),
            InterviewMode::FaceToFace,
            'HR Office, Administration Building',
            null,
            null,
            true,
        );
    }

    private function completedInterview(Application $application, User $admin, User $interviewer): void
    {
        $start = now()->subDay()->setTime(10, 0);

        $interview = $application->interviews()->create([
            'interviewer_id' => $interviewer->id,
            'scheduled_by' => $admin->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
            'mode' => InterviewMode::Online,
            'meeting_link' => 'https://meet.example.test/plp-demo',
            'status' => InterviewStatus::Completed,
            'completed_at' => $start->copy()->addHour(),
        ]);
        $application->forceFill(['status' => ApplicationStatus::DemoInterview, 'status_changed_at' => $start])->save();
        app(ApplicationTimeline::class)->add($application, 'demo_scheduled', $admin, ['starts_at' => $start->toDateTimeString(), 'interviewer' => $interviewer->name]);
        app(ApplicationTimeline::class)->add($application, 'demo_completed', $interviewer);
        $interview->touch();
    }

    private function offer(Application $application, User $admin): void
    {
        $position = $application->jobTitle;
        $type = EmploymentType::tryFrom((string) $position->employment_type) ?? EmploymentType::FullTime;
        $end = $type === EmploymentType::PartTime ? $position->contractEndDate() : null;
        $benefits = Benefit::query()->when($type === EmploymentType::PartTime, fn ($query) => $query->where('full_time_only', false))->pluck('id')->all();

        app(IssueOffer::class)->handle($admin, $application->fresh(), $type, 20, now()->addDay(), $end, $benefits);
    }

    private function document(Application $application, string $kind, string $title, string $filename, User $uploader): void
    {
        $contents = $this->pdf($title);

        $application->documents()->create([
            'uuid' => (string) Str::uuid(),
            'kind' => $kind,
            'original_name' => $filename,
            'mime_type' => 'application/pdf',
            'size' => strlen($contents),
            'sha256' => hash('sha256', $contents),
            'contents' => $contents,
            'uploaded_by' => $uploader->id,
        ]);
    }

    /**
     * A one-page PDF that says a line of text, so the document viewer has something real to show.
     */
    private function pdf(string $text): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $stream = "BT /F1 18 Tf 72 700 Td ({$text}) Tj ET\nBT /F1 11 Tf 72 670 Td (Sample document for the PLP Employee Management System.) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($number + 1)." 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
