<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EmployeeResource;
use App\Http\Resources\V1\EmployeeStatusResource;
use App\Models\Employee;
use App\Models\RateTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'college' => ['nullable', 'string', 'max:20'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:active,on_leave,inactive'],
            'updated_since' => ['nullable', 'date'],
        ]);

        $request->attributes->set('rate_schedule', RateTier::scheduleOn());

        $employees = $this->query()
            ->when($filters['college'] ?? null, fn (Builder $q, string $code) => $q->whereHas('college', fn (Builder $colleges) => $colleges->where('code', $code)))
            ->when($filters['job_title'] ?? null, fn (Builder $q, string $title) => $q->whereHas('jobTitle', fn (Builder $titles) => $titles->where('title', $title)))
            ->when($filters['updated_since'] ?? null, fn (Builder $q, string $date) => $q->where('updated_at', '>=', $date))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => match ($status) {
                'inactive' => $q->where('status', '!=', 'active'),
                'on_leave' => $q->where('status', 'active')->whereHas('leaveRequests', fn (Builder $leave) => $leave->covering()),
                default => $q->where('status', 'active')->whereDoesntHave('leaveRequests', fn (Builder $leave) => $leave->covering()),
            })
            ->orderBy('employee_no')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return EmployeeResource::collection($employees);
    }

    public function show(Request $request, string $employeeNo): EmployeeResource
    {
        $request->attributes->set('rate_schedule', RateTier::scheduleOn());

        return new EmployeeResource($this->query()->where('employee_no', $employeeNo)->firstOrFail());
    }

    public function status(string $employeeNo): EmployeeStatusResource
    {
        return new EmployeeStatusResource($this->query()->where('employee_no', $employeeNo)->firstOrFail());
    }

    /**
     * @return Builder<Employee>
     */
    private function query(): Builder
    {
        return Employee::query()
            ->with(['college', 'office', 'jobTitle'])
            ->withExists(['leaveRequests as is_on_leave' => fn (Builder $leave) => $leave->covering()]);
    }
}
