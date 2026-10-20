<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * A login: HR Admin, HR Staff, Employee, or Applicant. The role and account state are never mass assignable: only HR Admin
 * account actions change them.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'temp_password_expires_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'privacy_acknowledged_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return HasOne<ApplicantProfile, $this>
     */
    public function applicantProfile(): HasOne
    {
        return $this->hasOne(ApplicantProfile::class);
    }

    /**
     * The Blade layout this person's pages use.
     */
    public function layoutName(): string
    {
        return match ($this->role) {
            Role::HrAdmin, Role::HrStaff => 'layouts.admin',
            Role::Employee => 'layouts.employee',
            Role::Applicant => 'layouts.applicant',
        };
    }

    public function isApplicant(): bool
    {
        return $this->role === Role::Applicant;
    }

    public function isHr(): bool
    {
        return $this->role->isHr();
    }

    /**
     * First and last initials of the name, for the avatar.
     */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return mb_strtoupper(mb_substr($parts[0], 0, 1).(count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            Role::HrAdmin => 'HR Admin',
            Role::HrStaff => 'HR Staff',
            Role::Employee => 'Employee',
            Role::Applicant => 'Applicant',
        };
    }

    public function isHrAdmin(): bool
    {
        return $this->role === Role::HrAdmin;
    }

    /**
     * The first page this person sees after signing in.
     */
    public function homeUrl(): string
    {
        return match ($this->role) {
            Role::HrAdmin, Role::HrStaff => route('dashboard'),
            Role::Employee => route('employee.dashboard'),
            Role::Applicant => route('applicant.dashboard'),
        };
    }
}
