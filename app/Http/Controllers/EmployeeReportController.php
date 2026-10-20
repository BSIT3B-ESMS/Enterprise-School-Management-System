<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeStatus;
use App\Models\College;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Office;
use App\Models\RateTier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The Employees report page (HR Head only). It starts from the same search, tab, and filters as the Employees list and
 * hands the matching rows to the browser, which builds the PDF, Excel, or CSV file. Government IDs appear only as their
 * last four digits. Every export is logged through ExportLogController.
 */
class EmployeeReportController extends Controller
{
    public function show(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', 'in:faculty,non-teaching'],
            'q' => ['nullable', 'string', 'max:100'],
            'college' => ['nullable', 'integer'],
            'office' => ['nullable', 'integer'],
            'job_title' => ['nullable', 'integer'],
            'employment_type' => ['nullable', 'in:full_time,part_time'],
            'sort' => ['nullable', 'in:name,number,hired'],
            'dir' => ['nullable', 'in:asc,desc'],
        ]);

        $schedule = RateTier::scheduleOn();

        $rows = Employee::query()
            ->with(['jobTitle', 'college', 'office'])
            ->where('status', EmployeeStatus::Active)
            ->listed($filters)
            ->get()
            ->map(fn (Employee $employee): array => [
                'id' => $employee->employee_no,
                'name' => $employee->full_name,
                'birthdate' => $employee->birthdate?->toDateString() ?? '',
                'sex' => $employee->sex?->value ?? '',
                'maritalStatus' => $employee->marital_status?->value ?? '',
                'collegeId' => $employee->college_id,
                'college' => $employee->college?->name ?? '',
                'officeId' => $employee->office_id,
                'office' => $employee->office?->name ?? '',
                'jobTitleId' => $employee->job_title_id,
                'jobTitle' => $employee->jobTitle->title,
                'employmentType' => $employee->employment_type->label(),
                'hiredOn' => $employee->hired_on?->toDateString() ?? '',
                'status' => ucfirst($employee->status->value),
                'officialEmail' => $employee->official_email,
                'personalEmail' => $employee->personal_email ?? '',
                'phone' => $employee->phone ?? '',
                'address' => $employee->address ?? '',
                'basePay' => isset($schedule[$employee->jobTitle->salary_grade][$employee->salary_step])
                    ? (float) $schedule[$employee->jobTitle->salary_grade][$employee->salary_step]
                    : null,
                'ids' => collect(Employee::GOVERNMENT_ID_KEYS)->mapWithKeys(
                    fn (string $key) => [$key => isset($employee->maskedGovernmentIds()[$key]) ? '•••• '.$employee->maskedGovernmentIds()[$key] : ''],
                )->all(),
            ])
            ->all();

        return view('employees.report', [
            'rows' => $rows,
            'summary' => $this->summary($filters),
            'colleges' => College::query()->orderBy('name')->pluck('name', 'id'),
            'offices' => Office::query()->orderBy('name')->pluck('name', 'id'),
            'jobTitles' => JobTitle::query()->orderBy('title')->get(['id', 'title'])->unique('title')->pluck('title', 'id'),
            'backUrl' => route('employees.index', $filters),
        ]);
    }

    /**
     * The filters in words, for the preview and the printed report.
     *
     * @param  array<string, mixed>  $filters
     * @return array{filters: list<string>, sort: string}
     */
    private function summary(array $filters): array
    {
        $lines = [($filters['type'] ?? 'faculty') === 'faculty' ? 'Faculty' : 'Non-teaching staff'];

        if (filled($filters['q'] ?? null)) {
            $lines[] = "Search: “{$filters['q']}”";
        }

        if (filled($filters['college'] ?? null)) {
            $lines[] = 'College: '.(College::query()->whereKey($filters['college'])->value('name') ?? $filters['college']);
        }

        if (filled($filters['office'] ?? null)) {
            $lines[] = 'Office: '.(Office::query()->whereKey($filters['office'])->value('name') ?? $filters['office']);
        }

        if (filled($filters['job_title'] ?? null)) {
            $lines[] = 'Job title: '.(JobTitle::query()->whereKey($filters['job_title'])->value('title') ?? $filters['job_title']);
        }

        if (filled($filters['employment_type'] ?? null)) {
            $lines[] = $filters['employment_type'] === 'full_time' ? 'Full-time only' : 'Part-time only';
        }

        $sort = ['name' => 'Name', 'number' => 'Employee ID', 'hired' => 'Date Hired'][$filters['sort'] ?? 'name'];

        return ['filters' => $lines, 'sort' => "Sorted by {$sort}, ".(($filters['dir'] ?? 'asc') === 'desc' ? 'descending' : 'ascending')];
    }
}
