<?php

use App\Models\College;
use App\Models\CollegeDean;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\LeavePolicy;
use App\Models\Office;
use App\Models\Program;
use App\Models\RateTier;
use App\Models\User;
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
        ->and(Employee::query()->faculty()->count())->toBe(92);
});

it('seeds the five college secretaries and five office staff as non-teaching staff', function () {
    $this->seed();

    $secretaries = Employee::query()->whereHas('jobTitle', fn ($titles) => $titles->where('title', 'College Secretary'))->get();

    expect($secretaries->pluck('last_name')->sort()->values()->all())->toBe(['Cruz', 'Diosa', 'Nanez', 'Quiogue', 'San Jose'])
        ->and(Employee::query()->nonTeaching()->count())->toBe(10)
        ->and(Employee::query()->count())->toBe(102);
});

it('makes each college dean the person marked (Dean) in the list', function () {
    $this->seed();

    $deans = CollegeDean::query()->current()->with(['college', 'employee'])->get()
        ->mapWithKeys(fn (CollegeDean $dean) => [$dean->college->code => $dean->employee->last_name])
        ->sortKeys()
        ->all();

    expect($deans)->toBe(['CAS' => 'Alvero', 'CBA' => 'Cordova', 'CCS' => 'Tan', 'COE' => 'Zapanta', 'COED' => 'Unsay', 'CON' => 'Flores']);
});

it('leaves everything HR has not supplied about the roster blank', function () {
    $this->seed();

    $person = Employee::query()->where('last_name', 'Unsay')->sole();

    expect($person->first_name)->toBe('Maila')
        ->and($person->middle_name)->toBe('N.')
        ->and($person->hired_on)->toBeNull()
        ->and($person->birthdate)->toBeNull()
        ->and($person->sex)->toBeNull()
        ->and($person->phone)->toBeNull()
        ->and($person->government_ids)->toBeNull()
        ->and($person->emergencyContacts()->count())->toBe(0);
});

it('seeds seven published Instructor I openings, one per college', function () {
    $this->seed();

    $openings = JobTitle::query()->where('openings', '>', 0)->where('is_published', true)->with('college')->get();

    expect($openings)->toHaveCount(7)
        ->and($openings->pluck('title')->unique()->all())->toBe(['Instructor I'])
        ->and($openings->pluck('college.code')->sort()->values()->all())->toBe(['CAS', 'CBA', 'CCS', 'CIHM', 'COE', 'COED', 'CON']);
});

it('lets a non-teaching staff member also belong to a college', function () {
    $this->seed();

    $accountant = Employee::query()->whereHas('jobTitle', fn ($titles) => $titles->where('title', 'Accountant I'))->first();

    expect($accountant->office->code)->toBe('FIN')
        ->and($accountant->college->code)->toBe('CBA');
});

it('numbers employees from EMP-1001 with a unique official email each', function () {
    $this->seed();

    $employees = Employee::query()->orderBy('id')->get();

    expect($employees->first()->employee_no)->toBe('EMP-1001')
        ->and($employees->last()->employee_no)->toBe('EMP-1102')
        ->and($employees->pluck('employee_no')->unique())->toHaveCount(102)
        ->and($employees->pluck('official_email')->unique())->toHaveCount(102)
        ->and($employees->every(fn (Employee $employee) => str_ends_with($employee->official_email, '@plpasig.edu.ph')))->toBeTrue();
});

it('keeps seeded government ID numbers encrypted and shows only their last four digits', function () {
    $this->seed();

    $employee = Employee::query()->whereNotNull('government_ids')->first();
    $raw = DB::table('employees')->where('id', $employee->id)->value('government_ids');

    expect($raw)->not->toContain(preg_replace('/\D/', '', $employee->government_ids['tin']))
        ->and($employee->maskedGovernmentIds()['tin'])->toBe(substr(preg_replace('/\D/', '', $employee->government_ids['tin']), -4));
});

it('seeds full-time leave credits and none for part-time staff', function () {
    $this->seed();

    $credits = LeavePolicy::query()->get()->groupBy(fn (LeavePolicy $policy) => $policy->employment_type->value);

    expect($credits)->toHaveCount(1)
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

it('seeds eight steps for every pay band, running from the prototype minimum to its maximum', function () {
    $this->seed();

    $titleGrade = fn (string $title): int => JobTitle::query()->where('title', $title)->value('salary_grade');
    $amountAt = fn (string $title, int $step): string => RateTier::currentFor($titleGrade($title), $step)->monthly_amount;

    expect(RateTier::query()->count())->toBe(28 * 8)
        ->and($amountAt('Instructor I', 1))->toBe('36000.00')
        ->and($amountAt('Instructor III', 8))->toBe('46000.00')
        ->and($amountAt('Professor VI', 8))->toBe('110000.00')
        ->and($amountAt('IT Support Specialist', 1))->toBe('30000.00')
        ->and($amountAt('Accountant I', 8))->toBe('60000.00');
});

it('gives every job title the grade of its pay band, higher for senior ranks', function () {
    $this->seed();

    $grades = JobTitle::query()->whereIn('title', ['Instructor I', 'Assistant Professor I', 'Associate Professor I', 'Professor I'])
        ->where('college_id', College::query()->where('code', 'CCS')->value('id'))
        ->orderBy('salary_grade')->pluck('title')->all();

    expect($grades)->toBe(['Instructor I', 'Assistant Professor I', 'Associate Professor I', 'Professor I']);
});

it('seeds the two development HR Head accounts that sign in with the development password', function () {
    $this->seed();

    $heads = User::query()->where('role', 'hr_head')->orderBy('email')->get();

    expect($heads->pluck('email')->all())->toBe(['bellen_prespurtjames@plpasig.edu.ph', 'yumul_niccolofranco@plpasig.edu.ph'])
        ->and($heads->every(fn (User $user) => $user->isHr()))->toBeTrue()
        ->and($heads->every(fn (User $user) => Hash::check('password', $user->password)))->toBeTrue();
});

it('seeds one HR Staff and one Employee login on fictional office staff so every role can be tried', function () {
    $this->seed();

    $staff = User::query()->where('role', 'hr_staff')->sole();
    $employee = User::query()->where('role', 'employee')->sole();

    expect($staff->employee->office->code)->toBe('HR')
        ->and($employee->employee->jobTitle->title)->toBe('Administrative Assistant I')
        ->and($staff->email)->toBe($staff->employee->official_email)
        ->and(Hash::check('password', $employee->password))->toBeTrue();
});

it('does not create the development HR Head accounts in production', function () {
    $this->app->detectEnvironment(fn () => 'production');

    $this->app->make(DevelopmentUserSeeder::class)->run();

    expect(User::query()->count())->toBe(0);
});
