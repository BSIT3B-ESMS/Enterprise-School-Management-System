<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Multi-factor authentication
    |--------------------------------------------------------------------------
    |
    | When true, HR Admin and HR Staff must enroll an authenticator app (TOTP)
    | before they can use any other page. Employees may enroll but are not
    | forced to. It is paused for now (email and password only); the SDD
    | requires it to be turned on before production.
    |
    */

    'require_mfa' => (bool) env('EMS_REQUIRE_MFA', false),

    /*
    |--------------------------------------------------------------------------
    | Breached-password check
    |--------------------------------------------------------------------------
    |
    | New passwords are checked against the Have I Been Pwned range API. The
    | check needs internet access, so a machine that is offline must turn it
    | off (and the SDD's policy is then not fully met).
    |
    */

    'check_breached_passwords' => (bool) env('EMS_CHECK_BREACHED_PASSWORDS', true),

    /*
    |--------------------------------------------------------------------------
    | Idle session timeout (minutes)
    |--------------------------------------------------------------------------
    */

    'idle_timeout_minutes' => [
        'hr' => 15,
        'staff' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Temporary password lifetime (hours)
    |--------------------------------------------------------------------------
    |
    | A one-time password issued by HR stops working after this long.
    |
    */

    'temporary_password_hours' => 72,

    /*
    |--------------------------------------------------------------------------
    | Minimum active HR Admins
    |--------------------------------------------------------------------------
    |
    | HR Admin accounts cannot be deactivated or demoted if that would leave
    | fewer than this many active HR Admins, so the system is never locked out.
    |
    */

    'minimum_hr_admins' => 2,

    /*
    |--------------------------------------------------------------------------
    | Password help
    |--------------------------------------------------------------------------
    |
    | Staff passwords are reset by PLP HR. The sign-in page tells them to email
    | this address; there is no self-service reset link.
    |
    */

    'hr_email' => env('EMS_HR_EMAIL', 'hr@plpasig.edu.ph'),

    /*
    |--------------------------------------------------------------------------
    | Academic calendar
    |--------------------------------------------------------------------------
    |
    | PLP has two semesters a year, August to March. Each boundary is a month and day (MM-DD); the 2nd semester
    | ends in the following calendar year. Part-time contracts end with a semester or with the academic year.
    |
    */

    'working_hours' => ['start' => '08:00', 'end' => '17:00'],

    'never_email_domains' => ['plpasig.edu.ph'],

    'academic_calendar' => [
        'first_semester' => ['start' => '08-01', 'end' => '11-07'],
        'second_semester' => ['start' => '12-01', 'end' => '03-31'],
    ],

];
