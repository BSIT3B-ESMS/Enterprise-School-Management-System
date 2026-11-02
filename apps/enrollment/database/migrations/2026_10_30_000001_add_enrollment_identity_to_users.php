<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function(Blueprint $table) {
            $table->enum('role',['student','registrar','department_head'])->default('student');
            $table->boolean('must_change_password')->default(true);
        });
    }
    public function down(): void {
        Schema::table('users', fn(Blueprint $table) => $table->dropColumn(['role','must_change_password']));
    }
};