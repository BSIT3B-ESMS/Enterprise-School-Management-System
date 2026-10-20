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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->unique()->after('id')->constrained()->restrictOnDelete();
            $table->string('role', 20)->default('employee')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('temp_password_expires_at')->nullable()->after('must_change_password');
            $table->timestamp('password_changed_at')->nullable()->after('temp_password_expires_at');
            $table->timestamp('last_login_at')->nullable()->after('password_changed_at');
            $table->timestamp('privacy_acknowledged_at')->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
            $table->dropColumn([
                'role', 'is_active', 'must_change_password', 'temp_password_expires_at',
                'password_changed_at', 'last_login_at', 'privacy_acknowledged_at',
            ]);
        });
    }
};
