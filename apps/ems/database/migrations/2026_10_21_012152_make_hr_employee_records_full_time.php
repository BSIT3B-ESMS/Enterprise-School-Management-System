<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * An HR Admin or HR Staff login must belong to a full-time employee, so My Leave gives them full-time credits.
     * New grants are enforced in the account actions; this normalizes accounts that already existed.
     */
    public function up(): void
    {
        $employeeIds = DB::table('users')
            ->whereIn('role', ['hr_admin', 'hr_staff'])
            ->whereNotNull('employee_id')
            ->pluck('employee_id');

        if ($employeeIds->isEmpty()) {
            return;
        }

        DB::table('employees')->whereIn('id', $employeeIds)->update([
            'employment_type' => 'full_time',
            'weekly_hours' => 40,
            'contract_ends_on' => null,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // The previous employment type and contract end are not recoverable.
    }
};
