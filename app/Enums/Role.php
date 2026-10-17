<?php

namespace App\Enums;

/**
 * The four user groups in the architecture (decision T10).
 */
enum Role: string
{
    case Applicant = 'applicant';
    case Admission = 'admission';   // admission office personnel
    case Clinic = 'clinic';         // university clinic (medical requirements)
    case Sysadmin = 'sysadmin';     // accounts, backups, security settings

    public function label(): string
    {
        return match ($this) {
            self::Applicant => 'Applicant',
            self::Admission => 'Admission Personnel',
            self::Clinic => 'University Clinic',
            self::Sysadmin => 'System Administrator',
        };
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::Admission, self::Clinic], true);
    }

    /** Staff roles use an authenticator app for 2FA; applicants get an email code. */
    public function twoFactorMethod(): string
    {
        return $this === self::Applicant ? 'email' : 'totp';
    }
}
