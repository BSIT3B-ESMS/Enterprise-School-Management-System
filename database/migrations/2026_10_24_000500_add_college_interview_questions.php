<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The questions a college's dean asks at interviews; the dean can edit them. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colleges', function (Blueprint $table) {
            $table->json('interview_questions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('colleges', function (Blueprint $table) {
            $table->dropColumn('interview_questions');
        });
    }
};
