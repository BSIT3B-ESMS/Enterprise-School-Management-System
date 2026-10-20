<?php

use App\Models\Application;
use App\Models\College;
use App\Models\CollegeDean;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\LeavePolicy;
use App\Models\Office;
use App\Models\Program;
use App\Models\RateTier;
use App\Models\StoredDocument;
use App\Models\User;
use Database\Seeders\ApplicantSeeder;
use Database\Seeders\DevelopmentUserSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

it('seeds the seven PLP colleges with their programs and the four offices', function () {
    $this->seed();

    expect(College::query()->orderBy('code')->pluck('code')->all())
        ->toBe(['CAS', 'CBA', 'CCS', 'CIHM', 'COE', 'COED', 'CON'])
        ->and(Program::query()->count())->toBe(13)
        ->and(College::query()->where('code', 'COED')->first()->programs()->pluck('name', 'code')->all())
        ->toHaveKey('BEEd', 'Bachelor of Elementary Education')
        ->and(Office::query()->orderBy('code')->pluck('name', 'code')->all())
        ->toBe([
            'FIN' => 'Finance & Accounting',
            'HR' => 'Human Resources',
            'MIS' => 'Management Information System',
            'SSO' => 'Student Success Office',
        ]);
});

it('seeds the faculty roster the owner supplied', function () {
    $this->seed();

    $headcount = Employee::query()->faculty()
        ->whereNotIn('employees.id', Application::query()->whereNotNull('employee_id')->select('employee_id'))
        ->join('colleges', 'colleges.id', '=', 'employees.college_id')
        ->selectRaw('colleges.code as code, employees.employment_type as type, count(*) as total')
        ->groupBy('colleges.code', 'employees.employment_type')
        ->get()
        ->groupBy('code')
        ->map(fn ($rows) => [
            (int) $rows->firstWhere('type', 'full_time')?->total,
            (int) $rows->firstWhere('type', 'part_time')?->total,
        ])
        ->sortKeys()
        ->all();

    expect($headcount)->toBe([
        'CAS' => [11, 0],
        'CBA' => [3, 0],
        'CCS' => [11, 13],
        'CIHM' => [8, 8],
        'COE' => [6, 5],
        'COED' => [10, 16],
        'CON' => [1, 0],
    ])
        ->and(Employee::query()->faculty()->whereNotIn('employees.id', Application::query()->whereNotNull('employee_id')->select('employee_id'))->count())->toBe(92);
});

it('seeds the five college secretaries and five office staff as non-teaching staff', function () {
    $this->seed();

    $secretaries = Employee::query()->whereHas('jobTitle', fn ($titles) => $titles->where('title', 'College Secretary'))->get();

    expect($secretaries->pluck('last_name')->sort()->values()->all())->toBe(['Cruz', 'Diosa', 'Nanez', 'Quiogue', 'San Jose'])
        ->and(Employee::query()->nonTeaching()->count())->toBe(10)
        ->and(Employee::query()->count())->toBe(103);
});

it('makes each college dean the person marked (Dean) in the list', function () {
    $this->seed();

    $deans = CollegeDean::query()->current()->with(['college', 'employee'])->get()
        ->mapWithKeys(fn (CollegeDean $dean) => [$dean->college->code => $dean->employee->last_name])
        ->sortKeys()
        ->all();

    expect($deans)->toBe(['CAS' => 'Alvero', 'CBA' => 'Cordova', 'CCS' => 'Tan', 'COE' => 'Zapanta', 'COED' => 'Unsay', 'CON' => 'Flores']);
});

it('gives every roster member synthetic details, with the sex following the first name', function () {
    $this->seed();

    $person = Employee::query()->where('last_name', 'Unsay')->sole();

    expect($person->first_name)->toBe('Maila')
        ->and($person->middle_name)->toBe('N.')
        ->and($person->jobTitle->title)->toBe('Dean')
        ->and($person->sex->value)->toBe('Female')
        ->and($person->hired_on)->not->toBeNull()
        ->and($person->birthdate)->not->toBeNull()
        ->and($person->phone)->not->toBeNull()
        ->and($person->government_ids)->toHaveKeys(['tin', 'philhealth', 'pagibig'])
        ->and($person->emergencyContacts()->count())->toBe(1)
        ->and(Employee::query()->whereNull('hired_on')->orWhereNull('sex')->orWhereNull('government_ids')->count())->toBe(0)
        ->and(Employee::query()->where('personal_email', 'not like', '%@example.test')->count())->toBe(0);
});

it('seeds an Instructor I opening in each college and three part-time posts with an end of contract by term', function () {
    $this->seed();

    $openings = JobTitle::query()->where('is_published', true)->with('college')->get();
    $partTime = $openings->where('employment_type', 'part_time');

    expect($openings->where('title', 'Instructor I'))->toHaveCount(7)
        ->and($openings->where('title', 'Instructor I')->pluck('college.code')->sort()->values()->all())->toBe(['CAS', 'CBA', 'CCS', 'CIHM', 'COE', 'COED', 'CON'])
        ->and($partTime)->toHaveCount(3)
        ->and($partTime->every(fn (JobTitle $post) => $post->contract_term !== null && $post->contractEndDate() !== null))->toBeTrue();
});

it('seeds part-time contracts that end with a semester or the academic year, and none for full-time staff', function () {
    $this->seed();

    $partTime = Employee::query()->where('employment_type', 'part_time')->get();

    expect($partTime->isNotEmpty())->toBeTrue()
        ->and($partTime->every(fn (Employee $employee) => $employee->contract_ends_on !== null && $employee->weekly_hours < 40))->toBeTrue()
        ->and(Employee::query()->where('employment_type', 'full_time')->whereNotNull('contract_ends_on')->count())->toBe(0);
});

it('seeds applicants with working accounts across the pipeline, and a hired one who became an employee', function () {
    $this->seed();

    $statuses = Application::query()->pluck('status')->map->value->countBy()->all();

    expect($statuses)->toHaveKeys(['applied', 'demo_interview', 'offered', 'hired', 'rejected', 'cancelled'])
        ->and(User::query()->where('role', 'applicant')->count())->toBe(14)
        ->and(Hash::check(ApplicantSeeder::PASSWORD, User::query()->where('email', 'maricel.soriano@example.test')->value('password')))->toBeTrue()
        ->and(Application::query()->where('status', 'hired')->sole()->employee)->not->toBeNull()
        ->and(StoredDocument::query()->where('kind', 'resume')->count())->toBe(13);
});

it('lets a non-teaching staff member also belong to a college', function () {
    $this->seed();

    $accountant = Employee::query()->whereHas('jobTitle', fn ($titles) => $titles->where('title', 'Accountant I'))->first();

    expect($accountant->office->code)->toBe('FIN')
        ->and($accountant->college->code)->toBe('CBA');
});

it('numbers employees YY-00000 by hire year, counting from 1 each year in hire order', function () {
    $this->seed();

    $employees = Employee::query()->orderBy('hired_on')->orderBy('id')->get();

    expect($employees)->toHaveCount(103)
        ->and($employees->pluck('employee_no')->unique())->toHaveCount(103)
        ->and($employees->pluck('official_email')->unique())->toHaveCount(103)
        ->and($employees->every(fn (Employee $employee) => str_ends_with($employee->official_email, '@plpasig.edu.ph')))->toBeTrue();

    foreach ($employees->groupBy(fn (Employee $employee) => $employee->hired_on->format('y')) as $year => $hired) {
        expect($hired->pluck('employee_no')->all())
            ->toBe($hired->keys()->map(fn (int $index) => $year.'-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT))->all());
    }
});

it('keeps seeded government ID numbers encrypted and shows only their last four digits', function () {
    $this->seed();

    $employee = Employee::query()->whereNotNull('government_ids')->first();
    $raw = DB::table('employees')->where('id', $employee->id)->value('government_ids');

    expect($raw)->not->toContain(preg_replace('/\D/', '', $employee->government_ids['tin']))
        ->and($employee->maskedGovernmentIds()['tin'])->toBe(substr(preg_replace('/\D/', '', $employee->government_ids['tin']), -4));
});

it('seeds full-time leave credits and only vacation and sick leave for part-time staff', function () {
    $this->seed();

    $credits = LeavePolicy::query()->get()->groupBy(fn (LeavePolicy $policy) => $policy->employment_type->value);

    expect($credits)->toHaveCount(2)
        ->and($credits['part_time']->mapWithKeys(fn (LeavePolicy $policy) => [$policy->leave_type->value => (float) $policy->days_per_year])->all())->toBe(['vacation' => 15.0, 'sick' => 15.0])
        ->and($credits['full_time']->mapWithKeys(fn (LeavePolicy $policy) => [$policy->leave_type->value => (float) $policy->days_per_year])->all())
        ->toBe([
            'vacation' => 15.0,
            'sick' => 15.0,
            'special_privilege' => 3.0,
            'emergency' => 5.0,
            'maternity' => 105.0,
            'paternity' => 7.0,
            'solo_parent' => 7.0,
            'special_women' => 60.0,
            'vawc' => 10.0,
        ]);
});

it('seeds one monthly pay per salary grade, SG 1 to 33, at Step 1', function () {
    $this->seed();

    $titleGrade = fn (string $title): int => JobTitle::query()->where('title', $title)->value('salary_grade');

    expect(RateTier::query()->count())->toBe(33)
        ->and(RateTier::currentFor(1)->monthly_amount)->toBe('14634.00')
        ->and(RateTier::currentFor($titleGrade('Instructor I'))->monthly_amount)->toBe('33947.00')
        ->and($titleGrade('Dean'))->toBe(24)
        ->and(RateTier::currentFor(24)->monthly_amount)->toBe('102603.00');
});

it('gives every job title the grade of its pay band, higher for senior ranks', function () {
    $this->seed();

    $grades = JobTitle::query()->whereIn('title', ['Instructor I', 'Assistant Professor I', 'Associate Professor I', 'Professor I'])
        ->where('college_id', College::query()->where('code', 'CCS')->value('id'))
        ->orderBy('salary_grade')->pluck('title')->all();

    expect($grades)->toBe(['Instructor I', 'Assistant Professor I', 'Associate Professor I', 'Professor I']);
});

it('seeds the two development HR Admin accounts that sign in with the development password', function () {
    $this->seed();

    $heads = User::query()->where('role', 'hr_admin')->orderBy('email')->get();

    expect($heads->pluck('email')->all())->toBe(['bellen_prespurtjames@plpasig.edu.ph', 'yumul_niccolofranco@plpasig.edu.ph'])
        ->and($heads->every(fn (User $user) => $user->isHr()))->toBeTrue()
        ->and($heads->every(fn (User $user) => Hash::check('password', $user->password)))->toBeTrue();
});

it('gives every employee a login with the employee number as password, past first-login setup', function () {
    $this->seed();

    $staff = User::query()->where('role', 'hr_staff')->sole();
    $hiredIds = Application::query()->whereNotNull('employee_id')->pluck('employee_id');
    $employees = User::query()->where('role', 'employee')->whereNotIn('employee_id', $hiredIds)->get();

    expect($staff->employee->office->code)->toBe('HR')
        ->and($employees)->toHaveCount(101)
        ->and($employees->every(fn (User $user) => $user->email === $user->employee->official_email
            && Hash::check($user->employee->employee_no, $user->password)
            && ! $user->must_change_password
            && $user->onboarding_completed_at !== null))->toBeTrue()
        ->and(User::query()->whereNull('employee_id')->where('role', '!=', 'applicant')->pluck('role')->unique()->map->value->values()->all())->toBe(['hr_admin']);
});

it('does not create the development HR Admin accounts in production', function () {
    $this->app->detectEnvironment(fn () => 'production');

    $this->app->make(DevelopmentUserSeeder::class)->run();

    expect(User::query()->count())->toBe(0);
});
