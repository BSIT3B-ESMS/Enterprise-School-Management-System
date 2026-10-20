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
        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category');
            $table->foreignId('college_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('salary_grade');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('openings')->default(0);
            $table->boolean('is_published')->default(false);
            $table->string('employment_type')->nullable();
            $table->string('location')->nullable();
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->json('responsibilities')->nullable();
            $table->json('qualifications')->nullable();
            $table->json('requirements')->nullable();
            $table->timestamps();

            $table->index(['college_id', 'title']);
            $table->index(['office_id', 'title']);
        });

        // A job title belongs to exactly one college or one office.
        DB::statement('ALTER TABLE job_titles ADD CONSTRAINT job_titles_one_unit_check CHECK ((college_id IS NULL) <> (office_id IS NULL))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_titles');
    }
};
