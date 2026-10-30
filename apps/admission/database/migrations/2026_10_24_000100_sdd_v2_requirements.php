<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\ReferenceListSeeder;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * SDD v0.1 (2026-10-24) gaps:
 * - users: account lockout (FR-02) and the Employment System link (staff come from there)
 * - applicants: parent/guardian consent for minors (FR-31)
 * - exam_registrations: long brown envelope received (FR-17)
 * - api_clients: keys expire and are rotated every 90 days (NFR-05)
 * - strands + strand_program, barangays: reference lists staff maintain without code changes (FR-06, FR-26)
 * - new roles: admission_head, proctor, read_only (FR-30)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_id', 40)->nullable()->unique()->after('id');
            $table->string('source', 20)->default('local')->after('employee_id'); // local | employment
            $table->unsignedSmallInteger('failed_logins')->default(0)->after('last_login_at');
            $table->timestamp('locked_until')->nullable()->after('failed_logins');
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->string('guardian_consent_name', 150)->nullable()->after('has_guardian');
            $table->timestamp('guardian_consent_at')->nullable()->after('guardian_consent_name');
        });

        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->timestamp('envelope_received_at')->nullable()->after('form_checked');
            $table->foreignId('envelope_received_by')->nullable()->after('envelope_received_at')->constrained('users');
        });

        // NFR-08: when the file itself was deleted under the retention rule (the row stays for the audit trail)
        Schema::table('application_documents', function (Blueprint $table) {
            $table->timestamp('purged_at')->nullable();
        });

        Schema::table('api_clients', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('is_active');
        });
        DB::table('api_clients')->whereNull('expires_at')->update(['expires_at' => now()->addDays(90)]);

        Schema::create('strands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('strand_program', function (Blueprint $table) {
            $table->foreignId('strand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->primary(['strand_id', 'program_id']);
        });
        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('district');
            $table->string('name', 60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['district', 'name']);
        });

        // Starting lists; on a fresh install the seeder fills strand suggestions once programs exist
        (new ReferenceListSeeder)->run();

        foreach (['admission_head', 'proctor', 'read_only'] as $role) {
            RoleModel::findOrCreate($role, 'api');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('strand_program');
        Schema::dropIfExists('strands');
        Schema::dropIfExists('barangays');
        Schema::table('api_clients', fn (Blueprint $table) => $table->dropColumn('expires_at'));
        Schema::table('application_documents', fn (Blueprint $table) => $table->dropColumn('purged_at'));
        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('envelope_received_by');
            $table->dropColumn('envelope_received_at');
        });
        Schema::table('applicants', fn (Blueprint $table) => $table->dropColumn(['guardian_consent_name', 'guardian_consent_at']));
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['employee_id']);
            $table->dropColumn(['employee_id', 'source', 'failed_logins', 'locked_until']);
        });
        RoleModel::whereIn('name', ['admission_head', 'proctor', 'read_only'])->delete();
    }
};
