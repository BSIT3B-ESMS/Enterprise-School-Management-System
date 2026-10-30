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
            // index first: MySQL won't drop the unique index while the foreign key needs it
            $table->index('application_id');
            $table->dropUnique(['application_id']);
        });
    }

    public function down(): void
    {
        Schema::table('interview_assignments', function (Blueprint $table) {
            $table->unique('application_id');
            $table->dropIndex(['application_id']);
        });
    }
};
