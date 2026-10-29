<?php

namespace App\Http\Controllers;

use App\Actions\Employees\EmployeeActionException;
use App\Actions\Employees\ProposeAgreement;
use App\Enums\AuditAction;
use App\Enums\ContractTerm;
use App\Enums\EmploymentType;
use App\Enums\OfferKind;
use App\Enums\OfferStatus;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Offer;
use App\Services\AcademicCalendar;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Changes to where an employee works and on what terms: the HR Admin's job. Moving an employee to another job title, college, office,
 * or employment type (and a new contract when the old one ends) is an agreement the employee must accept by signing, like an
 * applicant accepts an offer. Nothing changes until they accept; declining leaves them as they are until their contract ends.
 */
class EmployeeAssignmentController extends Controller
{
    /**
     * Proposes a change of assignment or employment type.
     */
    public function transfer(Request $request, Employee $employee, ProposeAgreement $propose, AcademicCalendar $calendar): RedirectResponse
    {
        Gate::authorize('assign', $employee);

        return $this->propose($request, $employee, $propose, $calendar, OfferKind::Change);
    }

    /**
     * Sends a new contract offer (full-time, or part-time with an end of contract) when the current contract is ending.
     */
    public function renew(Request $request, Employee $employee, ProposeAgreement $propose, AcademicCalendar $calendar): RedirectResponse
    {
        Gate::authorize('assign', $employee);

        return $this->propose($request, $employee, $propose, $calendar, OfferKind::Renewal);
    }

    public function withdraw(Request $request, Employee $employee, Offer $offer, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('assign', $employee);
        abort_unless($offer->employee_id === $employee->id, 404);

        if (! $offer->isPending()) {
            return back()->with('error', 'Only an offer that is still waiting can be withdrawn.');
        }

        $offer->forceFill(['status' => OfferStatus::Withdrawn, 'responded_at' => now()])->save();
        $audit->log(AuditAction::Update, $offer, ['event' => 'offer_withdrawn'], $request->user());

        return back()->with('success', 'The offer was withdrawn.');
    }

    private function propose(Request $request, Employee $employee, ProposeAgreement $propose, AcademicCalendar $calendar, OfferKind $kind): RedirectResponse
    {
        $data = $request->validate([
            'job_title_id' => ['required', 'integer', 'exists:job_titles,id'],
            'extra_college_id' => ['nullable', 'integer', 'exists:colleges,id'],
            'extra_office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'weekly_hours' => ['nullable', 'integer', 'between:1,39'],
            'effective_date' => ['required', 'date'],
            'contract_term' => ['nullable', Rule::enum(ContractTerm::class)],
            'academic_year' => ['nullable', 'integer', 'between:2000,2100'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $end = null;

        if ($data['employment_type'] === EmploymentType::PartTime->value && filled($data['contract_term'] ?? null) && filled($data['academic_year'] ?? null)) {
            $end = $calendar->endOf(ContractTerm::from($data['contract_term']), (int) $data['academic_year']);
        }

        try {
            $offer = $propose->handle(
                $request->user(),
                $employee,
                $kind,
                JobTitle::query()->findOrFail($data['job_title_id']),
                $data['extra_college_id'] ?? null,
                $data['extra_office_id'] ?? null,
                EmploymentType::from($data['employment_type']),
                (int) ($data['weekly_hours'] ?? 20),
                Carbon::parse($data['effective_date']),
                $end,
                $data['reason'] ?? null,
            );
        } catch (EmployeeActionException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return to_route('employees.show', $employee)->with('success', "The {$offer->kind->label()} was sent to {$employee->first_name} to accept. Nothing changes until they sign.");
    }
}
