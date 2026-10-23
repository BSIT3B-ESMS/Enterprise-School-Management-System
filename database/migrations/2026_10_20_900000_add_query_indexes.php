<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the queries the office panel, reports and scheduler run
 * (docs/HEALTH_CHECK.md, finding 5). MySQL indexes every foreign key by
 * itself; SQLite (the demo default) does not, so without these the office
 * data scans the whole audit log once per batch of applicants.
 *
 * Indexes that start with a foreign key column are added only where the
 * database doesn't index foreign keys itself: on MySQL they would duplicate
 * the foreign key's index (or silently replace it, which blocks rollback).
 * Each index is skipped when its table or column doesn't exist yet or the
 * index is already there, so the migration is safe to run on any schema.
 */
return new class extends Migration
{
    /** index name => [table, columns, leads with a foreign key] */
    private const INDEXES = [
        'audit_logs_application_id_id_index' => ['audit_logs', ['application_id', 'id'], true],
        'applications_period_deleted_index' => ['applications', ['admission_period_id', 'deleted_at'], true],
        // Slot counts: accepted offers per program
        'applications_offered_status_index' => ['applications', ['offered_program_id', 'status'], true],
        'exam_sessions_period_index' => ['exam_sessions', ['admission_period_id'], true],
        'interview_schedules_period_index' => ['interview_schedules', ['admission_period_id'], true],
        // Forfeit and reminder jobs: status = offered, deadline before or within a window
        'applications_status_deadline_index' => ['applications', ['status', 'acceptance_deadline'], false],
    ];

    public function up(): void
    {
        $indexesForeignKeys = in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);

        foreach (self::INDEXES as $name => [$table, $columns, $leadsWithForeignKey]) {
            if ($leadsWithForeignKey && $indexesForeignKeys) {
                continue;
            }
            if (Schema::hasTable($table) && Schema::hasColumns($table, $columns) && ! Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $name => [$table]) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
            }
        }
    }
};
