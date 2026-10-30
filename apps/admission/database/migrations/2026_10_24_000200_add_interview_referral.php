<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * College Dean module: "Evaluation Results/Referral". A dean who doesn't
 * accept an applicant into the evaluated program can refer them to another
 * of their program choices (outcome "referred").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_assignments', function (Blueprint $table) {
            $table->foreignId('referred_program_id')->nullable()->after('outcome')->constrained('programs');
            $table->string('remark', 500)->nullable()->after('referred_program_id');
        });
    }

    public function down(): void
    {
        Schema::table('interview_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_program_id');
            $table->dropColumn('remark');
        });
    }
};
