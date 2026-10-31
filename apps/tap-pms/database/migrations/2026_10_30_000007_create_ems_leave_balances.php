<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ems_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->string('leave_type', 120);
            $table->decimal('remaining_days', 6, 2);
            $table->date('as_of');
            $table->timestamp('source_updated_at');
            $table->timestamps();
            $table->unique(['employee_id', 'leave_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ems_leave_balances');
    }
};
