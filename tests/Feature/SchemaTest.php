<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Program;
use App\Models\Requirement;
use App\Services\AuditLogger;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

/** Phase 1: data layer. */
class SchemaTest extends TestCase
{
    public function test_full_seed_runs_and_loads_setup_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(11, Program::count());
        $this->assertSame(8, Requirement::admission()->count());
        $this->assertSame(3, Requirement::medical()->count());
    }

    /** NFR-03: the audit log can't be edited through the model. */
    public function test_audit_log_model_refuses_update_and_delete(): void
    {
        $log = app(AuditLogger::class)->record('test.action', 'Did something');

        $this->expectException(LogicException::class);
        $log->update(['description' => 'Rewritten']);
    }

    /** NFR-03: the database trigger blocks raw edits too. */
    public function test_audit_log_trigger_blocks_raw_update(): void
    {
        app(AuditLogger::class)->record('test.action', 'Did something');

        $this->expectException(QueryException::class);
        DB::table('audit_logs')->update(['description' => 'Rewritten']);
    }

    public function test_audit_log_trigger_blocks_raw_delete(): void
    {
        app(AuditLogger::class)->record('test.action', 'Did something');

        try {
            DB::table('audit_logs')->delete();
            $this->fail('Delete should have been rejected.');
        } catch (QueryException) {
            $this->assertSame(1, AuditLog::count());
        }
    }
}
