<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every college's dean interviews its applicants (1st to 3rd choice), so all
 * programs start with the interview switched on. Staff can still turn it
 * off per program in Programs & requirements.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A program without a college has no dean to interview
        DB::table('programs')->whereNotNull('college_id')->update(['requires_interview' => true]);
    }

    public function down(): void
    {
        DB::table('programs')->where('code', '!=', 'BSN')->update(['requires_interview' => false]);
    }
};
