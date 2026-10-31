<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('integration_tokens', function (Blueprint $t) {
            $t->id(); $t->string('caller')->unique(); $t->char('token_hash',64)->unique(); $t->json('permissions');
            $t->timestamp('last_used_at')->nullable(); $t->timestamp('revoked_at')->nullable(); $t->timestamps();
        });
        Schema::create('api_idempotency_keys', function (Blueprint $t) {
            $t->id(); $t->foreignId('integration_token_id')->constrained()->cascadeOnDelete(); $t->uuid('key');
            $t->char('request_hash',64); $t->unsignedSmallInteger('response_status')->nullable(); $t->json('response_body')->nullable();
            $t->timestamp('expires_at'); $t->timestamps(); $t->unique(['integration_token_id','key']);
        });
        Schema::create('payroll_periods', function (Blueprint $t) {
            $t->id(); $t->uuid('period_id')->unique(); $t->date('starts_on'); $t->date('ends_on');
            $t->string('status')->default('draft'); $t->timestamps(); $t->unique(['starts_on','ends_on']);
        });
        Schema::create('payslips', function (Blueprint $t) {
            $t->id(); $t->uuid('payslip_id')->unique(); $t->uuid('period_id'); $t->foreign('period_id')->references('period_id')->on('payroll_periods');
            $t->string('employee_no'); $t->foreign('employee_no')->references('employee_no')->on('employees');
            $t->decimal('gross_pay',14,2); $t->decimal('deductions',14,2); $t->decimal('net_pay',14,2);
            $t->string('status')->default('draft'); $t->timestamp('released_at')->nullable(); $t->timestamps();
            $t->unique(['period_id','employee_no']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('payslips'); Schema::dropIfExists('payroll_periods'); Schema::dropIfExists('api_idempotency_keys'); Schema::dropIfExists('integration_tokens');
    }
};
