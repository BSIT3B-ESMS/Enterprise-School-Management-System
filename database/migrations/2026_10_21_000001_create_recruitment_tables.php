<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix', 10)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('sex')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        // Uploaded files live in the database for now. Everything reads and writes them through App\Services\DocumentStore,
        // so a separate document system can replace this table later without touching the callers.
        Schema::create('stored_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('documentable_type', 60);
            $table->unsignedBigInteger('documentable_id');
            $table->string('kind', 30);
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->string('sha256', 64);
            $table->binary('contents');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['documentable_type', 'documentable_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE stored_documents MODIFY contents LONGBLOB NOT NULL');
        }

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('job_title_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('applied');
            $table->date('applied_on');
            $table->json('education')->nullable();
            $table->json('work_history')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->text('cover_note')->nullable();
            $table->text('status_reason')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->index(['status', 'applied_on']);
            $table->index(['user_id', 'job_title_id']);
        });

        // The timeline of an application: what happened, who did it, and the details (old and new times, reasons).
        Schema::create('application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['application_id', 'created_at']);
        });

        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interviewer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('scheduled_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('mode', 20);
            $table->string('venue')->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamp('reschedule_requested_at')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->text('reschedule_available_days')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['interviewer_id', 'starts_at']);
            $table->index(['status', 'starts_at']);
        });

        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type', 20);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('venue')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('for_everyone')->default(false);
            $table->string('status', 20)->default('scheduled');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('application_events');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('stored_documents');
        Schema::dropIfExists('applicant_profiles');
    }
};
