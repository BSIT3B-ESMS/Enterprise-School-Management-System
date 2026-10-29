<?php

namespace Database\Seeders;

use App\Enums\ContractTerm;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Role;
use App\Models\Benefit;
use App\Models\CollegeDean;
use App\Models\Employee;
use App\Models\User;
use App\Services\AcademicCalendar;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Creates every seeded employee: the faculty roster plus the fictional office staff. The roster supplies only names,
 * colleges, and employment types, so the rest (hire date, sex, civil status, contacts, government IDs, and an
 * emergency contact) is synthetic. Employee numbers follow the hire date (YY-00000, counting from 1 each year). Each
 * employee gets a login whose password is their employee number, already past first-login setup.
 */
class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        fake()->seed(20261020);

        $people = collect([...FacultyRosterSeeder::specs(), ...OfficeStaffSeeder::specs()])
            ->map(fn (array $spec): array => [...$spec, 'hiredOn' => SyntheticProfile::hiredOn($spec['kind'])])
            ->sortBy('hiredOn')
            ->values();

        foreach ($people as $spec) {
            $this->createEmployee($spec);
        }
    }

    /**
     * Part-time contracts end with a semester or the academic year (August to March). Most run through the current academic year; a few
     * end with the 1st semester, and a few ended last academic year and wait for HR to decide.
     */
    private static function partTimeContractEnd(): string
    {
        $calendar = app(AcademicCalendar::class);
        $year = $calendar->startYearOf(now());
        $pick = fake()->numberBetween(1, 100);

        return match (true) {
            $pick <= 55 => $calendar->endOf(ContractTerm::AcademicYear, $year)->toDateString(),
            $pick <= 80 => $calendar->endOf(ContractTerm::FirstSemester, $year)->toDateString(),
            $pick <= 90 => $calendar->endOf(ContractTerm::SecondSemester, $year)->toDateString(),
            default => $calendar->endOf(ContractTerm::AcademicYear, $year - 1)->toDateString(),
        };
    }

    private function grantBenefits(Employee $employee): void
    {
        $benefits = Benefit::query()->where('is_active', true)->when($employee->employment_type === EmploymentType::PartTime, fn ($query) => $query->where('full_time_only', false))->pluck('id');

        foreach ($benefits as $id) {
            $employee->benefits()->attach($id, ['granted_on' => $employee->hired_on->toDateString()]);
        }
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function createEmployee(array $spec): void
    {
        $details = SyntheticProfile::details($spec['first'], $spec['last'], $spec['kind'], $spec['hiredOn']);

        $employee = new Employee;
        $employee->forceFill([
            'employee_no' => Employee::nextEmployeeNo(Carbon::parse($spec['hiredOn'])),
            'last_name' => $spec['last'],
            'first_name' => $spec['first'],
            'middle_name' => $spec['middle'],
            'suffix' => $spec['suffix'],
            'employment_type' => $spec['type'],
            'weekly_hours' => $spec['type'] === EmploymentType::PartTime ? fake()->randomElement([12, 15, 18, 20, 20]) : 40,
            'contract_ends_on' => $spec['type'] === EmploymentType::PartTime ? self::partTimeContractEnd() : null,
            'hired_on' => $spec['hiredOn'],
            'college_id' => $spec['college']?->id,
            'office_id' => $spec['office']?->id,
            'job_title_id' => $spec['jobTitle']->id,
            'official_email' => Employee::officialEmailFor($spec['last'], $spec['first']),
            'status' => EmployeeStatus::Active,
            ...$details,
        ]);
        $employee->setGovernmentIds(SyntheticProfile::governmentIds($spec['type'] === EmploymentType::FullTime));
        $employee->save();

        $this->grantBenefits($employee);

        $employee->emergencyContacts()->create([...SyntheticProfile::emergencyContact($details['address']), 'is_primary' => true]);

        if ($spec['isDean']) {
            CollegeDean::query()->create(['college_id' => $spec['college']->id, 'employee_id' => $employee->id]);
        }

        (new User)->forceFill([
            'employee_id' => $employee->id,
            'name' => $employee->full_name,
            'email' => $employee->official_email,
            'password' => $employee->employee_no,
            'role' => Role::Employee,
            'is_active' => true,
            'must_change_password' => false,
            'password_changed_at' => now(),
            'onboarding_completed_at' => now(),
        ])->save();
    }
}
