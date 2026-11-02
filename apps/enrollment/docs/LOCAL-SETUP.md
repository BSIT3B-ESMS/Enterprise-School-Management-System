# MariaDB / MySQL setup and recovery

## Runtime

The operational connection is mysql against the restored MariaDB 11.4.9 server on 127.0.0.1:3307. .env holds the private database name and credentials. PHP 8.4+ needs pdo_mysql, openssl, mbstring, fileinfo and the PDF extensions. configure-php.ps1 enables them in the private runtime. start-database.ps1 starts the preserved bundled MariaDB data directory when using the configured local port; it never initializes or replaces an existing data directory. Configure another MySQL server through .env if needed.

Use scripts/setup-local.ps1 for an already provisioned database and start.ps1 to launch. The server binds only 127.0.0.1:8000. No automatic default accounts are created. An HTTP port owned by another process is preserved.

## Verified transfer and rollback

On October 31, 2026 the user replaced the prior SQLite-only preference with MariaDB/MySQL. A verified encrypted SQLite backup was created first. Migrations were applied to a fresh, uniquely named MariaDB database, then 39 application tables were compared row-for-row, normalizing database numeric representations. All foreign-key relationships were checked. The target was activated through .env only after verification. Cache, sessions and queue implementation tables were recreated rather than copied. The existing password hashes, catalog and APP_KEY were preserved.

The original SQLite file, old MariaDB databases and private environment backups remain intact. They are rollback sources, not active writers. After new writes, reverting to any old database requires a maintenance window and reconciliation; do not switch connections casually or discard the current target. Archived SQLite readers/backups remain for recovery compatibility, not normal application operation.

## Backup and restore verification

Run php artisan enrollment:backup. MariaDB's official dump utility creates an InnoDB consistent snapshot. The backup is restored into an isolated verification schema, table and foreign-key checks run, and an encrypted .sql.enc artifact with checksum and counts is published under storage/app/private/backups. Credentials are supplied through a temporary private client file and removed after use, not embedded in command arguments.

Run php artisan enrollment:verify-backup <file>. It verifies encryption/checksum, restores into a unique plp_restore_check_* schema, validates tables and foreign keys and compares every table count. The operational schema is untouched. The local verifier uses the existing private local admin credential; an alternative deployment needs an authorized restore-verification connection. Protect verification schemas and APP_KEY. No command automatically overwrites the live database.

Run php scripts/test.php for the default MariaDB suite. It creates and cleans up a unique plp_test_* schema. phpunit.xml uses a non-operational placeholder; use the runner rather than pointing raw PHPUnit at the live database.

## Institution inputs

Confirm source contracts, employee teaching authorizations, grading, actual campus facilities and current curriculum versions. Configure and verify SMTP before sending invitations to real students. Student accounts come from Admission. No real student/professor records were copied from the prototype or supplied Node app.