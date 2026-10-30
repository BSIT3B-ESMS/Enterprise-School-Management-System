<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AuditLog;
use Tests\TestCase;

/** "What's your task?" pop-up: each choice is recorded in the audit log */
class TaskPickerTest extends TestCase
{
    public function test_task_choices_are_audited_and_shown_to_the_admin(): void
    {
        $officer = $this->makeUser(Role::Admission, ['name' => 'Rosa Reyes']);
        $head = $this->makeUser(Role::AdmissionHead);

        $this->asUser($officer)->postJson('/api/admin/task', ['task' => 'exam_office'])->assertNoContent();
        $this->assertDatabaseHas('audit_logs', ['user_id' => $officer->id, 'action' => 'staff.task', 'description' => 'Chose the task: Exam Office']);

        // "All tasks" is for the Admin only; unknown tasks are refused. The Super Admin has no picker
        $this->asUser($officer)->postJson('/api/admin/task', ['task' => 'all'])->assertUnprocessable();
        $this->asUser($officer)->postJson('/api/admin/task', ['task' => 'dean'])->assertUnprocessable();
        $this->asUser($head)->postJson('/api/admin/task', ['task' => 'all'])->assertNoContent();
        $this->asUser($this->makeUser(Role::CollegeDean))->postJson('/api/admin/task', ['task' => 'proctor'])->assertForbidden();
        $this->asUser($this->makeUser(Role::SuperAdmin))->postJson('/api/admin/task', ['task' => 'all'])->assertForbidden();
        $this->assertSame(2, AuditLog::where('action', 'staff.task')->count());

        // The Admin's audit log lists who chose what
        $audit = collect($this->asUser($head)->getJson('/api/admin/state')->assertOk()->json('data.audit'))->where('action', 'staff.task');
        $this->assertSame(['Chose the task: Exam Office', 'Chose the task: All tasks (Admin)'], $audit->pluck('text')->values()->all());
        $this->assertSame('Rosa Reyes', $audit->first()['by']);
    }
}
