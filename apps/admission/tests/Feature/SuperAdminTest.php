<?php

namespace Tests\Feature;

use App\Enums\Role;
use Tests\Concerns\BuildsApplications;
use Tests\TestCase;

/** Super admin: the office panel plus staff account management. */
class SuperAdminTest extends TestCase
{
    use BuildsApplications;

    public function test_super_admin_runs_the_office_panel_and_manages_accounts(): void
    {
        $boss = $this->makeUser(Role::SuperAdmin);

        $this->asUser($boss)->getJson('/api/admin/state')->assertOk();
        $this->asUser($boss)->patchJson('/api/admin/programs/BSIT', ['slots' => 150])->assertOk();

        $this->asUser($boss)->postJson('/api/admin/accounts', ['name' => 'Nurse Joy', 'email' => 'joy@plpasig.edu.ph', 'role' => 'clinic'])
            ->assertCreated()->assertJsonStructure(['data' => ['id', 'temporary_password']]);
        $this->asUser($boss)->postJson('/api/admin/accounts', ['name' => 'X', 'email' => 'x@plpasig.edu.ph', 'role' => 'super_admin'])->assertUnprocessable();
        $this->asUser($boss)->postJson('/api/admin/accounts', ['name' => 'Dean', 'email' => 'd@plpasig.edu.ph', 'role' => 'college_dean'])->assertUnprocessable();
        $this->asUser($boss)->getJson('/api/admin/accounts')->assertOk()->assertJsonFragment(['email' => 'joy@plpasig.edu.ph']);
    }

    public function test_admission_personnel_cannot_manage_accounts(): void
    {
        $this->asUser($this->officer())->getJson('/api/admin/accounts')->assertForbidden();
        $this->asUser($this->officer())->postJson('/api/admin/accounts', ['name' => 'X', 'email' => 'x@plpasig.edu.ph', 'role' => 'clinic'])->assertForbidden();
    }
}
