<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefits', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('full_time_only')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('benefit_id')->constrained()->restrictOnDelete();
            $table->date('granted_on');
            $table->string('details')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'benefit_id']);
        });

        // An offer of employment that the person accepts or declines in the system. Typing their full name when they accept is
        // their signature. kind: hire (an applicant), renewal (a contract that is ending), change (a new assignment or employment
        // type for an employee; it takes effect only if they accept).
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->foreignId('application_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->string('employment_type', 20);
            $table->unsignedTinyInteger('weekly_hours')->default(40);
            $table->foreignId('job_title_id')->constrained()->restrictOnDelete();
            $table->foreignId('college_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->json('summary');
            $table->text('terms');
            $table->json('benefit_ids')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->text('response_note')->nullable();
            $table->string('signed_name')->nullable();
            $table->string('signed_ip', 45)->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
        Schema::dropIfExists('employee_benefits');
        Schema::dropIfExists('benefits');
    }
};
