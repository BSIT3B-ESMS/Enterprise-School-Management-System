<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Multi-factor authentication
    |--------------------------------------------------------------------------
    |
    | When true, HR Head and HR Staff must enroll an authenticator app (TOTP)
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
    | Minimum active HR Heads
    |--------------------------------------------------------------------------
    |
    | HR Head accounts cannot be deactivated or demoted if that would leave
    | fewer than this many active HR Heads, so the system is never locked out.
    |
    */

    'minimum_hr_heads' => 2,

];
