<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\EmployeeStatus;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\LeaveRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The HR dashboard, all from the database: headcount by college, the recruitment pipeline, hires per year, recent hires, and recent
 * activity.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $active = Employee::query()->where('status', EmployeeStatus::Active);

        $headcount = College::query()
            ->withCount(['employees as headcount' => fn ($query) => $query->where('status', EmployeeStatus::Active)])
            ->orderByDesc('headcount')->orderBy('name')
            ->get(['id', 'code', 'name']);

        $pipelineCounts = Application::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $hiresPerYear = Employee::query()
            ->whereNotNull('hired_on')
            ->selectRaw('YEAR(hired_on) as year, count(*) as total')
            ->groupBy('year')->orderBy('year')
            ->pluck('total', 'year');

        $isAdmin = $request->user()->isHrAdmin();

        return view('dashboard', [
            'kpis' => [
                ['label' => 'Active employees', 'value' => (clone $active)->count(), 'url' => route('employees.index')],
                ['label' => 'On leave today', 'value' => LeaveRequest::query()->covering()->distinct('employee_id')->count('employee_id'), 'url' => route('leave.index', ['status' => 'approved'])],
                ['label' => 'Open positions', 'value' => JobTitle::query()->openForApplications()->count(), 'url' => route('job-positions.index', ['status' => 'open'])],
                ['label' => 'Applications to review', 'value' => (int) ($pipelineCounts[ApplicationStatus::Applied->value] ?? 0), 'url' => route('recruitment.index', ['status' => 'applied'])],
            ],
            'headcount' => $headcount,
            'headcountMax' => max(1, (int) $headcount->max('headcount')),
            'nonCollegeCount' => (clone $active)->whereNull('college_id')->count(),
            'pipeline' => collect(ApplicationStatus::pipeline())->map(fn (ApplicationStatus $status): array => ['status' => $status, 'count' => (int) ($pipelineCounts[$status->value] ?? 0)]),
            'pipelineTotal' => (int) $pipelineCounts->sum(),
            'hiresPerYear' => $hiresPerYear,
            'hiresMax' => max(1, (int) $hiresPerYear->max()),
            'recentHires' => Employee::query()->with(['jobTitle', 'college', 'office'])->whereNotNull('hired_on')->orderByDesc('hired_on')->orderByDesc('id')->limit(6)->get(),
            'activity' => AuditLog::query()->with('user')->latest('id')->limit(8)->get(),
            'endingContracts' => Employee::query()->where('status', EmployeeStatus::Active)->whereNotNull('contract_ends_on')->whereDate('contract_ends_on', '<=', now()->addDays(30))->count(),
            'isAdmin' => $isAdmin,
        ]);
    }
}
