<?php

namespace App\Http\Controllers\Api\Integration;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StaffDirectoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Employment System → OAS staff accounts (FR-30). X-API-Key of the API
 * client "employment". Idempotent: sending the same employee twice changes
 * nothing. See docs/INTEGRATIONS.md.
 */
class EmploymentController extends Controller
{
    public function __construct(private StaffDirectoryService $directory) {}

    /** GET: every staff account linked to the Employment System, for reconciliation. */
    public function index(): JsonResponse
    {
        $users = User::whereNotNull('employee_id')->with(['roles', 'deanOf'])->orderBy('name')->get();

        return response()->json(['data' => $users->map(fn (User $u) => $this->directory->payload($u))->all()]);
    }

    /**
     * POST: create or update up to 500 employees in one call.
     * Body: { employees: [{ employeeId, name, email, role, active?, college? }] }
     * Each one succeeds or fails on its own; the response lists both.
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employees' => ['required', 'array', 'min:1', 'max:500'],
            'employees.*.employeeId' => ['required', 'string', 'max:40', 'distinct'],
            'employees.*.name' => ['required', 'string', 'max:255'],
            'employees.*.email' => ['required', 'email:rfc', 'max:255'],
            'employees.*.role' => ['required', Rule::in(StaffDirectoryService::ROLES)],
            'employees.*.active' => ['sometimes', 'boolean'],
            'employees.*.college' => ['nullable', 'string', 'max:20'],
        ]);

        $results = [];
        foreach ($data['employees'] as $employee) {
            try {
                $outcome = $this->directory->upsert($employee);
                $results[] = ['employeeId' => $employee['employeeId'], 'status' => $outcome['action']];
            } catch (BusinessRuleException $e) {
                $results[] = ['employeeId' => $employee['employeeId'], 'status' => 'rejected', 'message' => $e->getMessage()];
            }
        }
        $rejected = count(array_filter($results, fn ($r) => $r['status'] === 'rejected'));

        return response()->json(['data' => $results], $rejected === count($results) ? 422 : 200);
    }

    /** DELETE: the employee left; the account is disabled (never deleted: the audit trail points to it). */
    public function destroy(string $employeeId): JsonResponse
    {
        $user = $this->directory->deactivate($employeeId);

        return response()->json(['data' => $this->directory->payload($user)]);
    }
}
