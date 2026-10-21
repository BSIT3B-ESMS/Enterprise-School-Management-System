<?php

namespace App\Http\Controllers;

use App\Actions\Recruitment\HireApplicant;
use App\Actions\Recruitment\IssueOffer;
use App\Actions\Recruitment\RecruitmentException;
use App\Actions\Recruitment\RejectApplication;
use App\Actions\Recruitment\ReopenApplication;
use App\Actions\Recruitment\UpdateOffer;
use App\Enums\ContractTerm;
use App\Enums\EmploymentType;
use App\Models\Application;
use App\Models\Offer;
use App\Services\AcademicCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * The decisions on an application: reject (HR Admin or HR Staff), issue an offer after the demo interview, and complete the hiring
 * once the offer is accepted (HR Admin only).
 */
class RecruitmentActionController extends Controller
{
    public function reject(Request $request, Application $application, RejectApplication $reject): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Give a reason for rejecting the application.']);

        try {
            $reject->handle($request->user(), $application, $data['reason']);
        } catch (RecruitmentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('recruitment.show', $application)->with('success', 'The application was rejected and the applicant was told.');
    }

    public function reopen(Request $request, Application $application, ReopenApplication $reopen): RedirectResponse
    {
        try {
            $reopen->handle($request->user(), $application);
        } catch (RecruitmentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('recruitment.show', $application)->with('success', 'The application was reopened. You can schedule a new demo interview.');
    }

    public function issueOffer(Request $request, Application $application, IssueOffer $issue, AcademicCalendar $calendar): RedirectResponse
    {
        abort_unless($request->user()->isHrAdmin(), 403);

        $data = $request->validate([
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'weekly_hours' => ['nullable', 'integer', 'between:1,39'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'contract_term' => ['nullable', Rule::enum(ContractTerm::class)],
            'academic_year' => ['nullable', 'integer', 'between:2000,2100'],
            'benefit_ids' => ['nullable', 'array'],
            'benefit_ids.*' => ['integer', 'exists:benefits,id'],
            'terms' => ['nullable', 'string', 'max:6000'],
        ]);

        $end = filled($data['end_date'] ?? null) ? Carbon::parse($data['end_date']) : null;

        if ($data['employment_type'] === EmploymentType::PartTime->value && filled($data['contract_term'] ?? null) && filled($data['academic_year'] ?? null)) {
            $end = $calendar->endOf(ContractTerm::from($data['contract_term']), (int) $data['academic_year']);
        }

        try {
            $issue->handle(
                $request->user(),
                $application,
                EmploymentType::from($data['employment_type']),
                (int) ($data['weekly_hours'] ?? 20),
                Carbon::parse($data['start_date']),
                $end,
                array_map('intval', $data['benefit_ids'] ?? []),
                $data['terms'] ?? null,
            );
        } catch (RecruitmentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return to_route('recruitment.show', $application)->with('success', 'The offer was sent. The applicant answers it in their account.');
    }

    public function updateOffer(Request $request, Application $application, Offer $offer, UpdateOffer $update, AcademicCalendar $calendar): RedirectResponse
    {
        abort_unless($request->user()->isHrAdmin() && $offer->application_id === $application->id, 403);

        $data = $request->validate([
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'weekly_hours' => ['nullable', 'integer', 'between:1,39'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'contract_term' => ['nullable', Rule::enum(ContractTerm::class)],
            'academic_year' => ['nullable', 'integer', 'between:2000,2100'],
            'benefit_ids' => ['nullable', 'array'],
            'benefit_ids.*' => ['integer', 'exists:benefits,id'],
            'terms' => ['nullable', 'string', 'max:6000'],
        ]);

        $isPartTime = $data['employment_type'] === EmploymentType::PartTime->value;
        $end = $isPartTime && filled($data['contract_term'] ?? null) && filled($data['academic_year'] ?? null)
            ? $calendar->endOf(ContractTerm::from($data['contract_term']), (int) $data['academic_year'])
            : null;

        try {
            $update->handle(
                $request->user(),
                $offer,
                EmploymentType::from($data['employment_type']),
                (int) ($data['weekly_hours'] ?? 20),
                Carbon::parse($data['start_date']),
                $end,
                array_map('intval', $data['benefit_ids'] ?? []),
                $data['terms'] ?? null,
            );
        } catch (RecruitmentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'The offer was updated and the applicant was told.');
    }

    public function hire(Request $request, Application $application, HireApplicant $hire): RedirectResponse
    {
        abort_unless($request->user()->isHrAdmin(), 403);

        try {
            $employee = $hire->handle($request->user(), $application);
        } catch (RecruitmentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('employees.show', $employee)->with('success', "Hired. Employee {$employee->employee_no} was created, and the applicant was told how to sign in.");
    }
}
