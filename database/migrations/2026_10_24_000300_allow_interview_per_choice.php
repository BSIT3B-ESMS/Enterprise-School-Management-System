<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An applicant is interviewed by the dean of each choice that requires an
 * interview (up to three), so an application can have several assignments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_assignments', function (Blueprint $table) {
            $table->dropUnique(['application_id']);
            $table->index('application_id');
        });
    }

    public function down(): void
    {
        Schema::table('interview_assignments', function (Blueprint $table) {
            $table->dropIndex(['application_id']);
            $table->unique('application_id');
        });
    }
};
