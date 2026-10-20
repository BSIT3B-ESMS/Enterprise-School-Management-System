<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no', 12)->unique();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix', 10)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('sex')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('employment_type');
            $table->unsignedTinyInteger('weekly_hours')->default(40);
            $table->date('hired_on')->nullable();
            $table->date('contract_ends_on')->nullable();
            $table->foreignId('college_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('job_title_id')->constrained()->restrictOnDelete();
            $table->string('official_email')->unique();
            $table->string('personal_email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->text('government_ids')->nullable();
            $table->json('government_id_hints')->nullable();
            $table->string('status')->default('active');
            $table->text('status_reason')->nullable();
            $table->date('status_effective_on')->nullable();
            $table->date('retention_review_at')->nullable();
            $table->timestamps();

            $table->index(['college_id', 'status']);
            $table->index(['office_id', 'status']);
            $table->index(['last_name', 'first_name']);
        });

        // Every employee belongs to a college (teaching), an office (non-teaching), or both.
        DB::statement('ALTER TABLE employees ADD CONSTRAINT employees_has_unit_check CHECK (college_id IS NOT NULL OR office_id IS NOT NULL)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
