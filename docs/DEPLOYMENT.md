# Deployment and institutional configuration

The current operational stack is Laravel / PHP 8.4+ / MariaDB through the MySQL driver. This supersedes the earlier SQLite-only configuration. Local restored MariaDB listens on loopback port 3307; production credentials and hosting require institutional configuration.

Serve only public/ over HTTPS. Keep .env, APP_KEY, database backups, runtime/admin credentials and logs outside web access. Configure APP_ENV=production, APP_DEBUG=false, the HTTPS APP_URL, secure sessions and approved proxy settings. Use a protected MariaDB/MySQL database with InnoDB, utf8mb4, foreign keys, a least-privilege application account and an independently authorized backup/restore-verification connection. Never regenerate the existing APP_KEY casually.

Back up before migrations. The backup command uses a consistent dump, encrypted storage and an isolated restore check. Schedule backups and approved off-host replication to meet the school's recovery policy; no retention destination, schedule or recovery-time guarantee is assumed. Verification schemas contain private data and require the same protection as source databases.

Confirm Admission/EMS payloads, credential ownership, SMTP delivery, official curricula, grades, credits, facilities, professor availability and COR approval. JSON/API exchange exists but does not establish live external connectivity. School acceptance, representative load tests and production operations remain institutional tasks. The automated independent-process test proves its tested capacity race, not general production availability.

See LOCAL-SETUP.md for the verified 39-table transfer and preserved rollback sources, and REFERENCE-INTEGRATION.md for functional mappings and source-prototype adaptations.