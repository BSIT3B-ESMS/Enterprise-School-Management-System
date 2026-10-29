<?php

namespace App\Actions\Recruitment;

use App\Actions\Accounts\CreateStaffAccount;
use App\Actions\Employees\CreateEmployee;
use App\Enums\ApplicationStatus;
use App\Enums\AuditAction;
use App\Enums\EmploymentType;
use App\Enums\Role;
use App\Mail\HiredMail;
use App\Models\Application;
use App\Models\Employee;
use App\Models\User;
use App\Services\ApplicationTimeline;
use App\Services\AuditLogger;
use App\Services\Notifier;
use App\Services\PersonalMail;
use Illuminate\Support\Facades\DB;

class HireApplicant
{
    public function __construct(
        private AuditLogger $audit,
        private ApplicationTimeline $timeline,
        private Notifier $notifier,
        private CreateEmployee $createEmployee,
        private CreateStaffAccount $createAccount,
        private PersonalMail $mail,
    ) {}

    /**
     * The HR Admin completes the hiring once the offer is accepted, in one transaction: the employee record is made from the
     * applicant's profile, an Employee login is created (official email, with the employee ID as the first password that
     * must be changed on first sign-in), the offered benefits are granted, the position loses an opening, and the application
     * becomes Hired. The applicant account stays. The applicant is told in the system and by email to their personal address.
     *
     * @throws RecruitmentException
     */
    public function handle(User $actor, Application $application): Employee
    {
        if (! $actor->isHrAdmin()) {
            throw new RecruitmentException('Only an HR Admin can complete a hiring.');
        }

        $application->loadMissing('applicant.applicantProfile', 'jobTitle', 'offers');

        $offer = $application->acceptedOffer();

        if ($application->status !== ApplicationStatus::Offered || $offer === null) {
            throw new RecruitmentException('The applicant has not accepted an offer yet.');
        }

        if ($application->employee_id !== null) {
            throw new RecruitmentException('This applicant is already hired.');
        }

        $employee = DB::transaction(function () use ($actor, $application, $offer): Employee {
            $profile = $application->applicant->applicantProfile;
            $isPartTime = $offer->employment_type === EmploymentType::PartTime;

            $employee = $this->createEmployee->handle($actor, [
                'last_name' => $profile->last_name,
                'first_name' => $profile->first_name,
                'middle_name' => $profile->middle_name,
                'suffix' => $profile->suffix,
                'birthdate' => $profile->birthdate?->toDateString(),
                'sex' => $profile->sex?->value,
                'marital_status' => $profile->marital_status?->value,
                'phone' => $profile->phone,
                'address' => $profile->address,
                'personal_email' => $application->applicant->email,
                'employment_type' => $offer->employment_type->value,
                'weekly_hours' => $offer->weekly_hours,
                'job_title_id' => $offer->job_title_id,
                'hired_on' => $offer->start_date->toDateString(),
                'contract_ends_on' => $isPartTime ? $offer->end_date?->toDateString() : null,
                'extra_college_id' => $offer->jobTitle->college_id === null ? $offer->college_id : null,
                'extra_office_id' => $offer->jobTitle->office_id === null ? $offer->office_id : null,
            ]);

            [$user] = $this->createAccount->handle($actor, $employee, Role::Employee);

            foreach ($offer->benefit_ids ?? [] as $benefitId) {
                $employee->benefits()->attach($benefitId, ['granted_on' => $offer->start_date->toDateString()]);
            }

            $application->forceFill(['status' => ApplicationStatus::Hired, 'employee_id' => $employee->id, 'status_changed_at' => now()])->save();

            $position = $application->jobTitle;
            $position->forceFill(['openings' => max(0, $position->openings - 1)])->save();

            $this->timeline->add($application, 'hired', $actor, ['employee_no' => $employee->employee_no]);
            $this->audit->log(AuditAction::Update, $application, ['event' => 'applicant_hired', 'employee_no' => $employee->employee_no, 'user_id' => $user->id], $actor);

            $this->notifier->notify(
                $application->applicant,
                'You are hired',
                "Welcome to PLP. Your employee login is {$employee->official_email} and your first password is your employee ID, {$employee->employee_no}. You will be asked to change it when you sign in.",
                route('applicant.applications.show', $application),
            );

            return $employee;
        });

        $this->mail->send($application->applicant->email, new HiredMail($application->applicant->applicantProfile->full_name, $application->jobTitle->title, $employee->official_email, $employee->employee_no, $employee->hired_on));

        return $employee;
    }
}
