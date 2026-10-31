<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('pms_sync_states', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('revision')->default(0);
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('pms_sync_states'); }
};
