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
        Schema::create('rate_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('salary_grade');
            $table->unsignedTinyInteger('step');
            $table->decimal('monthly_amount', 12, 2);
            $table->date('effective_date');
            $table->timestamps();

            $table->unique(['salary_grade', 'step', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_tiers');
    }
};
