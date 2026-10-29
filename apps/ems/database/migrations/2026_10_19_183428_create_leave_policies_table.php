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
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->string('leave_type', 30);
            $table->string('employment_type', 20);
            $table->foreignId('job_title_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('days_per_year', 5, 1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['leave_type', 'employment_type', 'job_title_id'], 'leave_policies_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};
