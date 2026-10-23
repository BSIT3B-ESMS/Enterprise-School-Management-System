<?php

namespace App\Enums;

/**
 * User groups (SDD v0.1 FR-30: Applicant, Admission Staff, Admission Head,
 * Proctor, Medical, Read-only, System Admin; plus the College Dean of the
 * earlier plan). The admission personnel key stays "admission" (decision
 * T10) and Medical stays "clinic", so existing accounts and tokens keep
 * working.
 *
 * Staff accounts will come from the Employment System
 * (POST /api/integrations/employees); until it is connected they are made
 * with `php artisan user:create`.
 */
enum Role: string
{
    case Applicant = 'applicant';
    case Admission = 'admission';          // Admission Staff (evaluator)
    case AdmissionHead = 'admission_head'; // also releases results (FR-20)
    case CollegeDean = 'college_dean';     // own college: interviews and read-only lists
    case Clinic = 'clinic';                // Medical: university clinic
    case Proctor = 'proctor';              // exam day: rosters, check-in, attendance (FR-16, FR-17)
    case ReadOnly = 'read_only';           // deans, registrar, DPO/auditor: view and reports only
    case Sysadmin = 'sysadmin';            // accounts, backups, security settings

    public function label(): string
    {
        return match ($this) {
            self::Applicant => 'Applicant',
            self::Admission => 'Admission Personnel',
            self::AdmissionHead => 'Admission Head',
            self::CollegeDean => 'College Dean',
            self::Clinic => 'University Clinic',
            self::Proctor => 'Exam Proctor',
            self::ReadOnly => 'Read-only',
            self::Sysadmin => 'System Administrator',
        };
    }

    /** Roles that sign in through the office side of the API. */
    public function isStaff(): bool
    {
        return ! in_array($this, [self::Applicant, self::Sysadmin], true);
    }

    /** Admission Staff and the Admission Head: full office work, exam scores included. */
    public function isAdmissionOffice(): bool
    {
        return in_array($this, [self::Admission, self::AdmissionHead], true);
    }

    /** Staff roles use an authenticator app for 2FA; applicants get an email code. */
    public function twoFactorMethod(): string
    {
        return $this === self::Applicant ? 'email' : 'totp';
    }

    /** Values a sysadmin or the Employment System may give a staff account. */
    public static function staffValues(): array
    {
        return array_values(array_map(fn (self $r) => $r->value, array_filter(self::cases(), fn (self $r) => $r !== self::Applicant)));
    }
}
