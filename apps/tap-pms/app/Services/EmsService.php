<?php

namespace App\Services;

use App\Models\{ApprovedLeave, Employee, LeaveBalance, SalaryHistory, WorkflowRequest, User};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{DB, Http, Storage, Validator};
use Illuminate\Validation\ValidationException;

/**
 * EMS owns employee identity, salary history, leave decisions AND leave balances.
 * All remote endpoints/field mappings must be explicitly agreed with EMS.
 */
class EmsService
{
    private function connection()
    {
        $base = config('ems.base_url');
        if (!config('ems.enabled') || !$base || !config('ems.token')) {
            throw ValidationException::withMessages(['ems' => 'Configure EMS host, app token and agreed API contract first.']);
        }
        $parts = parse_url($base);
        if (!is_array($parts) || !in_array($parts['scheme'] ?? '', ['https', 'http'], true)
            || (($parts['scheme'] ?? '') !== 'https' && !in_array($parts['host'] ?? '', ['localhost', '127.0.0.1', '::1'], true))) {
            throw ValidationException::withMessages(['ems' => 'EMS requires HTTPS except on localhost.']);
        }
        return Http::baseUrl(rtrim($base, '/'))
            ->withToken(config('ems.token'))->acceptJson()
            ->timeout(20)->connectTimeout(5)->withOptions(['allow_redirects' => false]);
    }

    private function path(string $path): string
    {
        if (!str_starts_with($path, '/api/v1/') || str_contains($path, '://')
            || str_contains($path, '..') || str_contains($path, '?')) {
            throw ValidationException::withMessages(['ems' => 'Use the agreed relative /api/v1 resource path.']);
        }
        return $path;
    }

    private function retryable(\Throwable $exception): bool
    {
        return $exception instanceof \Illuminate\Http\Client\ConnectionException
            || ($exception instanceof \Illuminate\Http\Client\RequestException
                && ($exception->response->status() === 429 || $exception->response->serverError()));
    }

    /** Fetch every cursor page and commit the entire snapshot atomically. */
    public function sync(string $resource): int
    {
        if (!in_array($resource, ['employees', 'leaves', 'balances'], true)) {
            throw ValidationException::withMessages(['ems' => 'Unsupported EMS sync resource.']);
        }
        $path = config('ems.'.$resource.'_path');
        $map = config('ems.'.match ($resource) {
            'employees' => 'employee_fields', 'leaves' => 'leave_fields', 'balances' => 'balance_fields',
        });
        if (!$path || !is_array($map) || !$map) {
            throw ValidationException::withMessages(['ems' => 'EMS path and field mapping are not configured.']);
        }
        $path = $this->path($path);
        $run = DB::table('ems_sync_runs')->insertGetId([
            'resource' => $resource, 'status' => 'running', 'record_count' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        try {
            $all = []; $cursor = null; $seen = []; $finished = false;
            for ($page = 0; $page < 100; $page++) {
                $params = ['limit' => 100];
                if ($cursor !== null) $params['cursor'] = $cursor;
                $response = $this->connection()
                    ->retry([1000, 4000, 16000], 0, fn ($e) => $this->retryable($e), false)
                    ->get($path, $params);
                if (!$response->successful()) throw new \RuntimeException('EMS request failed.');
                $body = $response->json();
                if (!is_array($body) || array_diff(array_keys($body), ['data', 'meta'])
                    || !is_array($body['data'] ?? null)
                    || !array_is_list($body['data']) || !is_array($body['meta'] ?? null)
                    || !is_bool($body['meta']['has_more'] ?? null)) {
                    throw new \RuntimeException('EMS list does not follow the data/meta cursor contract.');
                }
                foreach ($body['data'] as $row) {
                    if (!is_array($row) || array_is_list($row)) {
                        throw new \RuntimeException('EMS list contains an invalid record.');
                    }
                    $mapped = [];
                    foreach ($map as $target => $source) $mapped[$target] = data_get($row, $source);
                    $all[] = $mapped;
                    if (count($all) > 10000) throw new \RuntimeException('EMS sync record cap exceeded.');
                }
                if (!$body['meta']['has_more']) {
                    if (($body['meta']['next_cursor'] ?? null) !== null) {
                        throw new \RuntimeException('EMS final cursor must be null.');
                    }
                    $finished = true;
                    break;
                }
                $cursor = $body['meta']['next_cursor'] ?? null;
                if (!is_string($cursor) || $cursor === '' || isset($seen[$cursor])) {
                    throw new \RuntimeException('EMS pagination cursor is invalid or repeated.');
                }
                $seen[$cursor] = true;
            }
            if (!$finished) throw new \RuntimeException('EMS paging limit exceeded.');
            $count = DB::transaction(fn () => match ($resource) {
                'employees' => $this->importEmployees($all),
                'leaves' => $this->importLeaves($all),
                'balances' => $this->importBalances($all),
            });
            DB::table('ems_sync_runs')->where('id', $run)->update([
                'status' => 'success', 'record_count' => $count, 'updated_at' => now(),
            ]);
            Audit::record('ems_sync', 'ems_sync_runs', (string) $run, ['resource' => $resource, 'count' => $count]);
            return $count;
        } catch (\Throwable $e) {
            DB::table('ems_sync_runs')->where('id', $run)->update([
                'status' => 'failed',
                'message' => 'EMS exchange failed validation or connectivity. Existing records were preserved.',
                'updated_at' => now(),
            ]);
            throw ValidationException::withMessages([
                'ems' => 'EMS synchronization failed; current data was preserved. Verify EMS connectivity and contract.',
            ]);
        }
    }

    public function importEmployees(array $rows): int
    {
        foreach ($rows as $row) {
            $v = Validator::make($row, [
                'employee_no' => 'required|string|max:100',
                'full_name' => 'required|string|max:190',
                'department' => 'nullable|string|max:190',
                'position' => 'nullable|string|max:190',
                'monthly_rate' => ['required', 'string', 'regex:/^(0|[1-9][0-9]{0,7})\.[0-9]{2}$/'],
                'status' => 'required|in:active,on_leave,absent,inactive,retired,terminated,resigned',
                'salary_effective_on' => 'required|date_format:Y-m-d',
                'updated_at' => ['required', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|\+00:00)$/'],
            ])->validate();
            $sourceTime = Carbon::parse($v['updated_at'])->utc();
            $existing = Employee::where('employee_no', $v['employee_no'])->first();
            // An older incremental/out-of-order EMS payload must not roll back current salary/status.
            if ($existing && $existing->source_updated_at
                && Carbon::parse($existing->source_updated_at)->gt($sourceTime)) continue;
            $e = Employee::updateOrCreate(['employee_no' => $v['employee_no']], [
                'full_name' => $v['full_name'], 'department' => $v['department'] ?? null,
                'position' => $v['position'] ?? null, 'monthly_rate' => $v['monthly_rate'],
                'status' => $v['status'], 'source_system' => 'EMS',
                'source_updated_at' => $sourceTime,
            ]);
            if($existing){foreach(['full_name','department','position','status']as$field){$before=$existing->getRawOriginal($field);$after=$e->$field;if($before!==$after)\App\Models\EmployeeHistory::create(['employee_id'=>$e->id,'field'=>$field,'old_value'=>$before,'new_value'=>$after,'source_updated_at'=>$sourceTime,'observed_at'=>now()]);}}
            SalaryHistory::updateOrCreate([
                'employee_id' => $e->id, 'effective_on' => $v['salary_effective_on'],
            ], ['monthly_rate' => $v['monthly_rate'], 'source_reference' => 'EMS']);
            if (!$e->eligible()) {
                User::where('employee_id', $e->id)->where('is_active', true)
                    ->update(['is_active' => false, 'ems_suspended' => true]);
            } else {
                User::where('employee_id', $e->id)->where('ems_suspended', true)
                    ->update(['is_active' => true, 'ems_suspended' => false]);
            }
        }
        return count($rows);
    }

    public function importLeaves(array $rows): int
    {
        foreach ($rows as $row) {
            $v = Validator::make($row, [
                'leave_id' => 'required|string|max:190',
                'employee_no' => 'required|string|exists:employees,employee_no',
                'starts_on' => 'required|date_format:Y-m-d',
                'ends_on' => 'required|date_format:Y-m-d|after_or_equal:starts_on',
                'is_paid' => ['required', fn ($a, $v, $fail) => is_bool($v) ? null : $fail('Paid leave must be a JSON boolean.')],
                'status' => 'required|in:approved,rejected,cancelled',
                'request_id' => 'nullable|uuid',
            ])->validate();
            $e = Employee::where('employee_no', $v['employee_no'])->firstOrFail();
            $previous = ApprovedLeave::where('leave_id', $v['leave_id'])->first();
            if ($previous && $previous->employee_id !== $e->id) {
                throw ValidationException::withMessages(['leave_id' => 'EMS leave ID cannot move to another employee.']);
            }
            ApprovedLeave::updateOrCreate(['leave_id' => $v['leave_id']], [
                'employee_id' => $e->id, 'starts_on' => $v['starts_on'],
                'ends_on' => $v['ends_on'], 'is_paid' => $v['is_paid'],
                'status' => $v['status'], 'source_payload' => $v,
            ]);
            if (!empty($v['request_id'])) {
                WorkflowRequest::where('request_id', $v['request_id'])
                    ->where('employee_id', $e->id)->where('type', 'leave')
                    ->update(['status' => $v['status'], 'ems_reference' => $v['leave_id'], 'reviewed_at' => now()]);
            }
        }
        return count($rows);
    }

    /** Mirror EMS-owned leave credits without deriving them from PMS applications. */
    public function importBalances(array $rows): int
    {
        foreach ($rows as $row) {
            $v = Validator::make($row, [
                'employee_no' => 'required|string|exists:employees,employee_no',
                'leave_type' => 'required|string|max:120',
                'remaining_days' => ['required', 'string', 'regex:/^(0|[1-9][0-9]{0,3})\.[0-9]{2}$/'],
                'as_of' => 'required|date_format:Y-m-d',
                'updated_at' => ['required', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|\+00:00)$/'],
            ])->validate();
            $employee = Employee::where('employee_no', $v['employee_no'])->firstOrFail();
            $existing = LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type', $v['leave_type'])->first();
            $sourceTime = Carbon::parse($v['updated_at'])->utc();
            if ($existing) {
                // EMS alone changes credits. Prevent a stale or ambiguous import from
                // overwriting a newer balance already mirrored in TAP-PMS.
                if ($existing->as_of && $existing->as_of->toDateString() > $v['as_of']) continue;
                if ($existing->source_updated_at && $existing->source_updated_at->gt($sourceTime)) continue;
                if ($existing->source_updated_at && $existing->source_updated_at->equalTo($sourceTime)) {
                    if ($existing->remaining_days !== $v['remaining_days']
                        || $existing->as_of?->toDateString() !== $v['as_of']) {
                        throw ValidationException::withMessages([
                            'balances' => 'Conflicting EMS leave balance with the same source timestamp.',
                        ]);
                    }
                    continue; // identical response replay: do not mutate the mirror
                }
            }
            LeaveBalance::updateOrCreate([
                'employee_id' => $employee->id, 'leave_type' => $v['leave_type'],
            ], [
                'remaining_days' => $v['remaining_days'], 'as_of' => $v['as_of'],
                'source_updated_at' => $sourceTime,
            ]);
        }
        return count($rows);
    }

    /** Submit a leave request without dropping proof files or falsely claiming EMS acceptance. */
    public function sendLeave(WorkflowRequest $q): void
    {
        if ($q->type !== 'leave' || !in_array($q->status, ['pending_ems', 'pending'], true)) {
            throw ValidationException::withMessages(['ems' => 'This leave request cannot be submitted.']);
        }
        $path = config('ems.leave_requests_path');
        $map = config('ems.leave_request_fields');
        if (!$path || !is_array($map) || !$map) {
            throw ValidationException::withMessages(['ems' => 'Configure the signed EMS leave endpoint and payload mapping first.']);
        }
        $path = $this->path($path);
        $employeeNo = Employee::findOrFail($q->employee_id)->employee_no;
        $canonical = [
            'request_id' => $q->request_id, 'employee_no' => $employeeNo,
            'starts_on' => $q->work_date, 'ends_on' => $q->payload['ends_on'] ?? null,
            'leave_type' => $q->payload['leave_type'] ?? null, 'reason' => $q->reason,
        ];
        // Required identifiers must survive any team-agreed remote naming differences.
        foreach (['request_id', 'employee_no', 'starts_on', 'ends_on'] as $required) {
            if (!in_array($required, array_values($map), true) || empty($canonical[$required])) {
                throw ValidationException::withMessages(['ems' => 'The agreed EMS mapping must include official IDs and leave dates.']);
            }
        }
        $payload = [];
        foreach ($map as $remote => $local) {
            if (!is_string($remote) || !preg_match('/^[a-z][a-z0-9_]*$/', $remote) || !array_key_exists($local, $canonical)) {
                throw ValidationException::withMessages(['ems' => 'Invalid EMS request field mapping.']);
            }
            $payload[$remote] = $canonical[$local];
        }
        $request = $this->connection()->withHeaders(['Idempotency-Key' => $q->request_id]);
        $stream = null;
        try {
            if ($q->attachment_path) {
                $field = config('ems.leave_proof_field');
                if (!is_string($field) || !preg_match('/^[a-z][a-z0-9_]*$/', $field)) {
                    throw ValidationException::withMessages([
                        'ems' => 'Proof attachment cannot be submitted until EMS documents its multipart file field. No request was sent.',
                    ]);
                }
                if (!Storage::disk('local')->exists($q->attachment_path)) {
                    throw ValidationException::withMessages(['ems' => 'The stored proof file is missing; request was not sent.']);
                }
                $stream = Storage::disk('local')->readStream($q->attachment_path);
                if (!is_resource($stream)) {
                    throw ValidationException::withMessages(['ems' => 'Unable to read the stored proof file.']);
                }
                $name = basename((string) ($q->attachment_name ?: 'leave-proof'));
                $request = $request->asMultipart()->attach($field, $stream, $name);
            }
            $response = $request->retry([1000, 4000, 16000], 0, fn ($e) => $this->retryable($e), false)
                ->post($path, $payload);
            if (!$response->successful()) {
                throw ValidationException::withMessages(['ems' => 'EMS did not accept the leave request; it remains pending.']);
            }
            $body = $response->json();
            if (!is_array($body) || array_diff(array_keys($body), ['data', 'meta'])
                || !isset($body['data']) || !is_array($body['data'])
                || array_is_list($body['data']) || isset($body['status'])) {
                throw ValidationException::withMessages(['ems' => 'EMS acknowledgement did not follow the agreed data format. Request remains pending.']);
            }
            if (isset($body['data']['request_id']) && $body['data']['request_id'] !== $q->request_id) {
                throw ValidationException::withMessages(['ems' => 'EMS acknowledged a different request ID. Request remains pending.']);
            }
            if (isset($body['data']['employee_no']) && $body['data']['employee_no'] !== $employeeNo) {
                throw ValidationException::withMessages(['ems' => 'EMS acknowledged a different employee number. Request remains pending.']);
            }
            $q->update(['status' => 'pending_ems']);
            Audit::record('leave_sent_to_ems', 'workflow_requests', $q->request_id, ['has_proof' => (bool) $q->attachment_path]);
        } finally {
            if (is_resource($stream)) fclose($stream);
        }
    }
}
