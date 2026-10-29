<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_consumers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('token_hash', 64)->unique();
            $table->json('abilities');
            $table->boolean('is_active')->default(true);
            $table->string('agreement_ref')->nullable();
            $table->timestamp('token_issued_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('api_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_consumer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('route', 120);
            $table->unsignedSmallInteger('status');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_access_logs');
        Schema::dropIfExists('api_consumers');
    }
};
