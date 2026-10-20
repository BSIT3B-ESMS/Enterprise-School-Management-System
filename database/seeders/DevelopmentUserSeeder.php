<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentUserSeeder extends Seeder
{
    /**
     * The two development HR Admin accounts. They exist for local, testing, and staging use only and must be removed
     * before production. The seeder stores the password directly, so the 12-character rule (which applies when a user
     * sets a password) does not apply to it.
     *
     * @var array<string, string>
     */
    private const HR_HEADS = [
        'yumul_niccolofranco@plpasig.edu.ph' => 'Niccolo Franco Yumul',
        'bellen_prespurtjames@plpasig.edu.ph' => 'Prespurt James Bellen',
    ];

    /**
     * Where the development HR Admin accounts get their password from; see the note above.
     */
    private const DEVELOPMENT_PASSWORD = 'password';

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        foreach (self::HR_HEADS as $email => $name) {
            $this->saveAccount(['email' => $email, 'name' => $name], Role::HrAdmin);
        }

        // The HR office officer becomes HR Staff so every role can be tried locally. Their password stays the employee number like everyone else's.
        $hrOfficer = Employee::query()->whereHas('office', fn ($offices) => $offices->where('code', 'HR'))->first();

        $hrOfficer?->user()->update(['role' => Role::HrStaff]);
    }

    /**
     * @param  array{email: string, name: string, employee_id?: int}  $details
     */
    private function saveAccount(array $details, Role $role): void
    {
        $user = User::query()->firstOrNew(['email' => $details['email']]);

        $user->forceFill([
            ...$details,
            'password' => self::DEVELOPMENT_PASSWORD,
            'role' => $role,
            'is_active' => true,
            'must_change_password' => false,
            'onboarding_completed_at' => now(),
        ])->save();
    }
}
