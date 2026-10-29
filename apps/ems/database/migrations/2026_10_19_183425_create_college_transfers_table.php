<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('college_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_college_id')->nullable()->constrained('colleges')->restrictOnDelete();
            $table->foreignId('to_college_id')->nullable()->constrained('colleges')->restrictOnDelete();
            $table->foreignId('from_office_id')->nullable()->constrained('offices')->restrictOnDelete();
            $table->foreignId('to_office_id')->nullable()->constrained('offices')->restrictOnDelete();
            $table->foreignId('from_job_title_id')->constrained('job_titles')->restrictOnDelete();
            $table->foreignId('to_job_title_id')->constrained('job_titles')->restrictOnDelete();
            $table->date('effective_date');
            $table->text('reason')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('college_transfers');
    }
};
