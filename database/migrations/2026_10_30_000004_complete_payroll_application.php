<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('users',function(Blueprint $t){$t->string('role')->default('employee');$t->foreignId('employee_id')->nullable()->constrained();$t->boolean('is_active')->default(true);$t->text('totp_secret')->nullable();$t->timestamp('totp_confirmed_at')->nullable();$t->unsignedBigInteger('totp_last_counter')->nullable();$t->text('recovery_codes')->nullable();});
  Schema::table('work_schedules',function(Blueprint $t){$t->unsignedSmallInteger('grace_minutes')->default(0);$t->json('work_days')->nullable();});
  Schema::table('employees',function(Blueprint $t){$t->string('source_system')->default('EMS');$t->timestamp('source_updated_at')->nullable();});
  Schema::table('payslips',function(Blueprint $t){$t->json('calculation')->nullable();$t->foreignId('released_by')->nullable()->constrained('users');});
  Schema::table('payroll_periods',function(Blueprint $t){$t->timestamp('computed_at')->nullable();$t->foreignId('computed_by')->nullable()->constrained('users');});
  Schema::create('application_settings',function(Blueprint $t){$t->string('key')->primary();$t->json('value')->nullable();$t->timestamps();});
  Schema::create('salary_history',function(Blueprint $t){$t->id();$t->foreignId('employee_id')->constrained();$t->date('effective_on');$t->decimal('monthly_rate',12,2);$t->string('source_reference')->nullable();$t->timestamps();$t->unique(['employee_id','effective_on']);});
  Schema::create('workflow_requests',function(Blueprint $t){$t->id();$t->uuid('request_id')->unique();$t->foreignId('employee_id')->constrained();$t->string('type');$t->date('work_date');$t->text('reason');$t->json('payload');$t->string('attachment_path')->nullable();$t->string('attachment_name')->nullable();$t->string('status')->default('pending');$t->string('ems_reference')->nullable();$t->foreignId('reviewed_by')->nullable()->constrained('users');$t->timestamp('reviewed_at')->nullable();$t->text('decision_note')->nullable();$t->timestamps();});
  Schema::create('approved_leaves',function(Blueprint $t){$t->id();$t->string('leave_id')->unique();$t->foreignId('employee_id')->constrained();$t->date('starts_on');$t->date('ends_on');$t->boolean('is_paid');$t->string('status');$t->json('source_payload')->nullable();$t->timestamps();});
  Schema::create('payroll_adjustments',function(Blueprint $t){$t->id();$t->uuid('adjustment_id')->unique();$t->foreignId('employee_id')->constrained();$t->foreignId('workflow_request_id')->nullable()->unique()->constrained('workflow_requests');$t->date('applies_on');$t->string('kind');$t->string('label');$t->decimal('amount',12,2);$t->foreignId('created_by')->constrained('users');$t->timestamps();});
  Schema::create('kiosk_devices',function(Blueprint $t){$t->id();$t->string('name');$t->char('token_hash',64)->unique();$t->timestamp('revoked_at')->nullable();$t->timestamp('last_used_at')->nullable();$t->timestamps();});
  Schema::create('attendance_punches',function(Blueprint $t){$t->id();$t->uuid('punch_id')->unique();$t->foreignId('employee_id')->constrained();$t->foreignId('kiosk_device_id')->nullable()->constrained();$t->date('work_date');$t->string('event_type');$t->timestamp('occurred_at');$t->string('photo_path');$t->uuid('client_key')->unique();$t->timestamps();$t->unique(['employee_id','work_date','event_type']);});
  Schema::create('audit_events',function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained();$t->string('action');$t->string('entity');$t->string('entity_id')->nullable();$t->json('details')->nullable();$t->timestamp('created_at')->useCurrent();});
  Schema::create('report_exports',function(Blueprint $t){$t->id();$t->uuid('report_id')->unique();$t->string('control_number')->unique();$t->string('type');$t->json('filters');$t->json('snapshot');$t->foreignId('user_id')->constrained();$t->timestamps();});
  Schema::create('ems_sync_runs',function(Blueprint $t){$t->id();$t->string('resource');$t->string('status');$t->unsignedInteger('record_count')->default(0);$t->text('message')->nullable();$t->timestamps();});
 }
 public function down(): void {
  foreach(['ems_sync_runs','report_exports','audit_events','attendance_punches','kiosk_devices','payroll_adjustments','approved_leaves','workflow_requests','salary_history','application_settings'] as $name)Schema::dropIfExists($name);
  Schema::table('payroll_periods',function(Blueprint $t){$t->dropConstrainedForeignId('computed_by');$t->dropColumn('computed_at');});
  Schema::table('payslips',function(Blueprint $t){$t->dropConstrainedForeignId('released_by');$t->dropColumn('calculation');});
  Schema::table('employees',fn(Blueprint $t)=>$t->dropColumn(['source_system','source_updated_at']));
  Schema::table('work_schedules',fn(Blueprint $t)=>$t->dropColumn(['grace_minutes','work_days']));
  Schema::table('users',function(Blueprint $t){$t->dropConstrainedForeignId('employee_id');$t->dropColumn(['role','is_active','totp_secret','totp_confirmed_at','totp_last_counter','recovery_codes']);});
 }
};
