<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\BusinessRuleException;
use App\Models\College;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Staff accounts from the Employment System (FR-30). Every user except
 * applicants will come from there: it sends each employee who works in
 * admissions with the OAS role they should have, and the OAS keeps the
 * account in step (create, rename, change role, disable). Until it is
 * connected, the sysadmin uses `php artisan user:create`.
 *
 * Accounts are matched by employee ID, then by email (so accounts made by
 * hand are linked the first time they arrive). Sysadmin accounts are never
 * managed from outside. Every change is audited with before/after values
 * (NFR-09).
 */
class StaffDirectoryService
{
    /** Roles the Employment System may give. */
    public const ROLES = ['admission', 'admission_head', 'college_dean', 'clinic', 'proctor', 'read_only'];

    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{employeeId: string, name: string, email: string, role: string, active?: bool, college?: string|null}  $employee
     * @return array{user: User, action: string}  action: created | updated | unchanged
     */
    public function upsert(array $employee, string $source = 'employment'): array
    {
        return DB::transaction(function () use ($employee, $source) {
            $role = Role::from($employee['role']);
            $email = Str::lower(trim($employee['email']));
            $user = User::withTrashed()->where('employee_id', $employee['employeeId'])->first()
                ?? User::withTrashed()->where('email', $email)->first();

            if ($user && $user->hasRole(Role::Applicant->value)) {
                throw new BusinessRuleException("{$email} belongs to an applicant account and can't become a staff account.");
            }
            if ($user && $user->hasRole(Role::Sysadmin->value)) {
                throw new BusinessRuleException("{$email} is a system administrator account, which is managed only on the server.");
            }
            if ($user && User::where('email', $email)->whereKeyNot($user->id)->exists()) {
                throw new BusinessRuleException("Another account already uses {$email}.");
            }

            $college = null;
            if ($role === Role::CollegeDean) {
                $college = College::where('code', strtoupper((string) ($employee['college'] ?? '')))->first()
                    ?? throw new BusinessRuleException('A college_dean needs a college code: '.College::orderBy('code')->pluck('code')->implode(', ').'.');
            }

            $active = (bool) ($employee['active'] ?? true);
            $before = $user ? $this->snapshot($user) : null;
            $created = ! $user;
            $user ??= new User(['password' => Str::password(32)]); // signs in after a password reset (or SSO later)
            $user->trashed() && $user->restore();
            $user->forceFill([
                'employee_id' => $employee['employeeId'],
                'source' => $source,
                'name' => trim($employee['name']),
                'email' => $email,
                'is_active' => $active,
            ])->save();
            if ($user->primaryRole() !== $role) {
                $user->syncRoles([$role->value]);
            }
            if ($college && $college->dean_user_id !== $user->id) {
                $college->update(['dean_user_id' => $user->id]);
            }

            $after = $this->snapshot($user->fresh());
            if ($created) {
                $this->audit->record('account.provisioned', "Created {$role->label()} account for {$email} from the Employment System", $user, meta: ['after' => $after], actorName: 'API · employment');

                return ['user' => $user, 'action' => 'created'];
            }
            if ($before === $after) {
                return ['user' => $user, 'action' => 'unchanged'];
            }
            $changed = array_keys(array_diff_assoc(array_map('strval', $after), array_map('strval', $before)));
            $this->audit->record(in_array('role', $changed, true) ? 'account.role_changed' : 'account.updated',
                "Updated {$email} from the Employment System: ".implode(', ', $changed), $user,
                meta: ['before' => array_intersect_key($before, array_flip($changed)), 'after' => array_intersect_key($after, array_flip($changed))],
                actorName: 'API · employment');

            return ['user' => $user, 'action' => 'updated'];
        });
    }

    /** The employee left or no longer works in admissions: the account is disabled at once. */
    public function deactivate(string $employeeId): User
    {
        $user = User::where('employee_id', $employeeId)->firstOrFail();
        if ($user->is_active) {
            $user->forceFill(['is_active' => false])->save();
            $this->audit->record('account.disabled', "Disabled {$user->email} (Employment System)", $user, meta: ['before' => ['active' => true], 'after' => ['active' => false]], actorName: 'API · employment');
        }

        return $user;
    }

    public function payload(User $user): array
    {
        return [
            'employeeId' => $user->employee_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->primaryRole()?->value,
            'college' => $user->deanOf?->code,
            'active' => $user->is_active,
            'source' => $user->source,
            'lastLoginAt' => $user->last_login_at?->toIso8601String(),
        ];
    }

    private function snapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->primaryRole()?->value,
            'college' => $user->deanOf?->code,
            'active' => $user->is_active ? '1' : '0',
        ];
    }
}
