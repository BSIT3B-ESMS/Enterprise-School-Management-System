<?php

return [

    /*
    | Uploads (decision T8): 5 MB to match the Nginx cap in the architecture.
    */
    'uploads' => [
        'max_kb' => (int) env('ADMISSION_UPLOAD_MAX_KB', 5120),
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
        'image_mimes' => ['jpg', 'jpeg', 'png'],
    ],

    /*
    | Where uploaded files go (decision T5). "local" = private disk on this
    | server; "dms" = the university Document Management System.
    */
    'documents' => [
        'driver' => env('DOCUMENT_STORE', 'local'),
        'local_disk' => env('DOCUMENT_LOCAL_DISK', 'local'),
        'dms' => [
            'base_url' => env('DMS_BASE_URL'),
            'api_key' => env('DMS_API_KEY'),
            'timeout' => (int) env('DMS_TIMEOUT', 20),
        ],
    ],

    /*
    | Enrollment system (architecture diagram). Off until it exists (T9).
    | api_key: what we send; inbound_key: what Enrollment must send us.
    */
    'enrollment' => [
        'enabled' => (bool) env('ENROLLMENT_ENABLED', false),
        'base_url' => env('ENROLLMENT_BASE_URL'),
        'api_key' => env('ENROLLMENT_API_KEY'),
        'inbound_key' => env('ENROLLMENT_INBOUND_KEY'),
        'timeout' => (int) env('ENROLLMENT_TIMEOUT', 20),
    ],

    /*
    | Backups (php artisan app:backup, nightly in the scheduler)
    */
    'backups' => [
        'path' => env('BACKUP_PATH', storage_path('app/backups')),
        'keep' => (int) env('BACKUP_KEEP', 14),
        'mysqldump' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),
    ],

    // Times are stored in UTC and shown to staff and applicants in Philippine time
    'display_timezone' => env('DISPLAY_TIMEZONE', 'Asia/Manila'),

    // Internal: suggested pass mark shown to staff only (NFR-02)
    'passing_score' => 60,

    // Application number prefix: AD-{year}-{5 digits}
    'number_prefix' => 'AD',
    'permit_prefix' => 'EX',

    // 2FA for applicants by email code (decision T4). Off until the UI has a code screen.
    'applicant_email_2fa' => (bool) env('APPLICANT_EMAIL_2FA', false),
];
