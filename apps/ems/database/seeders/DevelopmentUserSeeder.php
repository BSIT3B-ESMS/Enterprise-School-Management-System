<?php

namespace Database\Seeders;

use App\Actions\Accounts\EnsureHrEmployment;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Role;
use App\Models\Benefit;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Office;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DevelopmentUserSeeder extends Seeder
{
    /**
     * The two development HR Admin accounts. They exist for local, testing, and staging use only and must be removed
     * before production. The seeder stores the password directly, so the 12-character rule (which applies when a user
     * sets a password) does not apply to it. Each one has a full-time employee record in the HR office, so My Leave gives
     * them the same full-time credits as any other full-time employee.
     *
     * @var array<string, array{first: string, last: string, title: string}>
     */
    private const HR_HEADS = [
        'yumul_niccolofranco@plpasig.edu.ph' => ['first' => 'Niccolo Franco', 'last' => 'Yumul', 'title' => 'Human Resource Management Officer I'],
        'bellen_prespurtjames@plpasig.edu.ph' => ['first' => 'Prespurt James', 'last' => 'Bellen', 'title' => 'Human Resource Management Assistant'],
    ];

    /**
     * The HR Staff account used locally. Their password stays the employee number like everyone else's.
     */
    private const HR_STAFF_EMAIL = 'manansala_lourdes@plpasig.edu.ph';

    /**
     * Where the development HR Admin accounts get their password from; see the note above.
     */
    private const DEVELOPMENT_PASSWORD = 'password';

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $actor = User::query()->where('role', Role::HrAdmin)->first()
            ?? User::query()->firstOrFail();

        foreach (self::HR_HEADS as $email => $head) {
            $employee = $this->employeeFor($email, $head, $actor);

            $this->saveAccount([
                'email' => $email,
                'name' => "{$head['first']} {$head['last']}",
                'employee_id' => $employee->id,
            ], Role::HrAdmin);
        }

        $hrOfficer = Employee::query()->where('official_email', self::HR_STAFF_EMAIL)->first();

        if ($hrOfficer !== null) {
            app(EnsureHrEmployment::class)->handle($actor, $hrOfficer);
            $hrOfficer->user()->update(['role' => Role::HrStaff]);
        }
    }

    /**
     * The employee record for a development HR Admin, created on an older database that has the account but no employee.
     *
     * @param  array{first: string, last: string, title: string}  $head
     */
    private function employeeFor(string $email, array $head, User $actor): Employee
    {
        $employee = Employee::query()->where('official_email', $email)->first();

        if ($employee !== null) {
            app(EnsureHrEmployment::class)->handle($actor, $employee);

            return $employee;
        }

        $office = Office::query()->where('code', 'HR')->firstOrFail();
        $jobTitle = JobTitle::query()->where('office_id', $office->id)->where('title', $head['title'])->firstOrFail();
        $hiredOn = SyntheticProfile::hiredOn('staff');
        $details = SyntheticProfile::details($head['first'], $head['last'], 'staff', $hiredOn);

        $employee = new Employee;
        $employee->forceFill([
            'employee_no' => Employee::nextEmployeeNo(Carbon::parse($hiredOn)),
            'last_name' => $head['last'],
            'first_name' => $head['first'],
            'middle_name' => null,
            'suffix' => null,
            'employment_type' => EmploymentType::FullTime,
            'weekly_hours' => Employee::FULL_TIME_HOURS,
            'contract_ends_on' => null,
            'hired_on' => $hiredOn,
            'college_id' => null,
            'office_id' => $office->id,
            'job_title_id' => $jobTitle->id,
            'official_email' => $email,
            'status' => EmployeeStatus::Active,
            ...$details,
        ]);
        $employee->setGovernmentIds(SyntheticProfile::governmentIds(true));
        $employee->save();

        $employee->benefits()->syncWithoutDetaching(
            Benefit::query()->where('is_active', true)->pluck('id')
                ->mapWithKeys(fn (int $id): array => [$id => ['granted_on' => $employee->hired_on->toDateString()]])
                ->all()
        );

        $employee->emergencyContacts()->create([...SyntheticProfile::emergencyContact($details['address']), 'is_primary' => true]);

        return $employee;
    }

    /**
     * @param  array{email: string, name: string, employee_id?: int}  $details
     */
    private function saveAccount(array $details, Role $role): void
    {
        $user = User::query()->firstOrNew(['email' => $details['email']]);

        $user->forceFill([
            ...$details,
            'password' => self::DEVELOPMENT_PASSWORD,
            'role' => $role,
            'is_active' => true,
            'must_change_password' => false,
            'onboarding_completed_at' => now(),
        ])->save();
    }
}
