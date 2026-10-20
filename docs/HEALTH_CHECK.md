# System health check: performance, optimization, gaps

**Date:** 2026-10-20 · **Scope:** the `backend` branch as it stands in the working tree on this date, including the uncommitted work in progress for the updated plan (DMS outbox, offers, interviews, reports, 2FA). Line references point to that working tree, so a few may shift once the work in progress is committed.

**Method:** I read the routes, middleware, migrations, services, presenters and scheduled jobs, ran the test suite, and checked query plans against the SQLite demo database (read-only). This review changes no application code: another session is building features in the same files, so each fix below is written as a recommendation for the owner of that code.

## Summary

| # | Finding | Area | Severity |
|---|---|---|---|
| 1 | Slot counts are not limited to the admission period | Correctness | **High** |
| 2 | Accepting an offer re-checks nothing under the lock (race with double click and with forfeit) | Correctness | **High** |
| 3 | Two uploads at the same time can leave two "current" versions of a document | Correctness | **High** |
| 4 | Test suite: 20 of 58 tests fail on the working tree | Quality | **High** |
| 5 | SQLite (now the demo default) has no index on foreign keys: the office data scans the whole audit log | Performance | Medium |
| 6 | Office data sends the entire office audit log, which grows forever | Performance | Medium |
| 7 | Bulk result release runs about 10 queries per applicant in one long transaction | Performance | Medium |
| 8 | DMS file upload: blocking retries, a consumed stream on retry, retries on 4xx | Performance / correctness | Medium |
| 9 | DMS outbox: no ordering per applicant, endless hourly retries, possible duplicate jobs | Integration | Medium |
| 10 | Queued notifications are dispatched inside transactions with `after_commit` off | Integration | Medium (Low on the database queue) |
| 11 | Smaller N+1 queries and repeated lookups | Performance | Low |
| 12 | The duplicate-applicant check cannot use its index | Performance | Low |
| 13 | Data retention: nothing prunes the outbox, notifications, devices or orphaned files | Operations | Low |
| 14 | Missing config validation (DMS URL), login timing, minor robustness | Robustness | Low |

The system's base is solid: status changes go through one service, history and audit tables are append-only (triggers on MySQL and SQLite), the outbox keeps DMS outages from blocking staff, exam seats and program slots are taken under row locks, office data is streamed in batches, and rate limits cover sign-in, registration and uploads. Most findings are edge cases (concurrency, the second admission period, growth over time) rather than everyday failures.

---

## High

### 1. Slot counts are not limited to the admission period

[Program::filledCounts](../app/Models/Program.php#L45) and the check in [OfferService::accept](../app/Services/OfferService.php#L84-L85) count every application in a slot-holding status (`slot_accepted`, `for_medical`, `admitted`) for a program, **across all admission periods**. `programs.slots` is a single number per program, so when the next admission period opens, last year's admitted students fill this year's slots. The new period would show programs as full, and applicants would get "All slots have been taken".

**Fix:** add `where('admission_period_id', AdmissionPeriod::current()->id)` to both queries (or pass the period in). If slots differ per period, move `slots` to a `program_period` table later. Add a test that creates an admitted applicant in an older period.

### 2. Accepting an offer: status checked on a stale copy, not under the lock

[OfferService::accept](../app/Services/OfferService.php#L67) checks `status` and the deadline on the model loaded before the transaction. Inside the transaction it locks the **program** row but never re-reads the **application**:

- **Double click / two tabs:** both requests pass the `Offered` check. The second waits for the program lock, then runs `Offered → SlotAccepted → ForMedical` again on its stale copy. The result is duplicate status history, audit lines and DMS events. If the program is at its last slot, the second request counts the first one's slot and tells an applicant who has already been accepted that "All slots have been taken".
- **Accept vs. the hourly forfeit job:** [forfeitExpired](../app/Services/OfferService.php) uses no lock either, so an acceptance at 23:59 and the forfeit can both write their transitions.

**Fix:** at the start of the transaction, re-load with `Application::whereKey($application->id)->lockForUpdate()->first()`, then repeat the status and deadline checks on that row. Do the same in `forfeitExpired` (re-load and lock each row, skip it if it's no longer `Offered`). The same pattern also helps `ApplicationService::submit` (double submit writes two audit lines).

### 3. Concurrent uploads of the same requirement

[DocumentService::upload](../app/Services/DocumentService.php#L55-L66) reads the current version outside the transaction, then marks it not current and inserts `version + 1`. Two uploads at the same time (double click, slow mobile network with retry) both read the same `$existing`, so both insert a version marked current. Later code uses `->first()`, so which file staff review is arbitrary, and `missingDocuments` / `submitMedical` key by requirement and silently keep one of the two.

**Fix:** inside the transaction, lock the application row (`Application::whereKey(...)->lockForUpdate()`), then re-read the current document and compute the version. The [upload throttle](../app/Providers/AppServiceProvider.php) (30/min) does not prevent this.

### 4. Test suite: 20 of 58 tests fail on the working tree

`php vendor/bin/phpunit`: **37 passed, 20 failed** (58 tests). Every failure comes from the work in progress, not from a regression in finished code:

| Cause | Tests affected |
|---|---|
| `REQUIRE_VERIFIED_EMAIL` now defaults to `true` ([config/admission.php:115](../config/admission.php#L115)), but the test helpers create applicants without `email_verified_at`, so every submit returns 422 "Confirm your email address first" | 19 tests: ApplicantApplication (3), Document (4), Exam (5), Notification (3), Setup (1), Enrollment (3) |
| The DMS config moved from `admission.documents.dms.*` to `admission.dms.*`, but [DocumentTest:187](../tests/Feature/DocumentTest.php#L187) still sets the old keys, so the client has no base URL (500: "URI must include a scheme and host") | `DocumentTest::test_dms_store` |
| Also: `EnrollmentTest` covers the direct Enrollment hand-off that decision D12 removed (`SendToEnrollment`, `EnrollmentHandoff` deleted), so its 3 tests stay red after the email fix | the same 3 `EnrollmentTest` tests |

**Fix:** have the applicant factory or `BuildsApplications` mark the email verified (with one test that keeps it unverified to cover the rule), update the DMS keys in `DocumentTest`, and rewrite `EnrollmentTest` for the callback-only flow. There are still no tests for the new services (offers/forfeit, interviews, reports, DMS outbox, QR check-in, PDFs).

---

## Medium

### 5. SQLite: foreign keys have no index, so the office data scans the audit log

Laravel's `foreignId()->constrained()` creates an index automatically on MySQL (InnoDB requires one) but **not on SQLite**. Since commit `5c42834`, SQLite is the demo default. The query plan on `database/database.sqlite`:

```
SELECT * FROM audit_logs WHERE application_id IN (…) ORDER BY id      → SCAN audit_logs
SELECT id FROM applications WHERE admission_period_id = ? AND deleted_at IS NULL → SCAN applications
```

[OfficeStatePresenter](../app/Http/Presenters/OfficeStatePresenter.php) runs the first query once per batch of 1,000 applicants, so the whole audit log is scanned once per batch. The same applies to every other `foreignId` column used in a `WHERE`, such as `exam_sessions.admission_period_id` and `interview_schedules.admission_period_id`. Tables with a composite index that starts with the foreign key (`application_status_history`, `application_documents`, `application_program_choices`) are fine.

**Fix:** one new migration with explicit indexes, harmless on MySQL: `audit_logs(application_id, id)`, `applications(admission_period_id, deleted_at)`, `exam_sessions(admission_period_id)`, `interview_schedules(admission_period_id)`. Also useful on both databases: `applications(status, acceptance_deadline)` for the forfeit/reminder jobs and `applications(offered_program_id, status)` for slot counts.

### 6. The office data loads the entire office audit log

`'audit' =>` in [OfficeStatePresenter::state](../app/Http/Presenters/OfficeStatePresenter.php#L67) loads **every** audit row without an application, since the system started, into memory (not streamed like the applicants). It already excludes applicant sign-ins, but staff sign-ins, logouts, settings changes and every inbound API call (`api.inbound`, one row per Enrollment callback) all go there. It grows with every working day and is sent on every office sign-in.

**Fix:** send only the latest N (e.g. 500) here and add a paginated `GET /api/admin/audit?before=<id>` for the audit screen. The bigger change is still R3 in PROGRESS.md (paging the office screens; ~5–7 s sign-in at 20,000 applicants).

### 7. Bulk result release: about 10 queries per applicant, one transaction

`POST /api/admin/results/release` accepts up to 1,000 IDs. For each application, [ExamService::release](../app/Services/ExamService.php#L228) writes the result, an audit row and an outbox row and dispatches a job, then calls `afterResultReleased` (a `firstChoice()` query, a lazy `period` load, a transition with history plus another outbox row and job). After that, the [controller](../app/Http/Controllers/Api/Office/ExamController.php#L100) runs `fresh()` plus the full presenter (about 7 queries) **once per application**. Releasing 1,000 results means roughly 10,000+ queries in a single request, with one transaction holding row locks the whole time.

**Fix:** eager-load `choices.program`, `period` and `result` before the loop. Commit in chunks (e.g. 100 per transaction) so one failure doesn't roll back everything and locks stay short. Make the presenter's batch method public (`applicants($staff, $ids)` already builds many applicants in about 7 queries total) and call it once for all released IDs.

### 8. DMS file upload path

[DmsDocumentStore](../app/Services/Documents/DmsDocumentStore.php#L77) uploads inside the HTTP request (by design, §9.1), but:

- `retry(3, 500, throw: false)` with `timeout(20)` can block a PHP-FPM worker for 60+ seconds when the DMS is slow, and the ClamAV scan runs before it. A handful of slow uploads can use up the FPM pool.
- The file is attached as an `fopen()` handle opened once, **outside** the retry. After the first attempt reads it to the end, a retried attempt can send an empty or truncated body. *Plausible: this depends on how Guzzle rewinds the multipart stream; it should be tested with `Http::fake` returning a 500 first.*
- The retry has no `when:` condition, so 4xx responses (validation, auth) are retried too.

**Fix:** retry only on `ConnectionException` or 5xx (`retry(2, 300, fn ($e) => $e instanceof ConnectionException || $e->response?->serverError())`), open the stream inside the retry callback (or pass the file contents), and lower the timeout (5–10 s). Set Nginx `proxy_read_timeout` / FPM `request_terminate_timeout` to match.

### 9. DMS outbox delivery

[DmsSync](../app/Services/Dms/DmsSync.php) and [DeliverDmsEvent](../app/Jobs/DeliverDmsEvent.php):

- **No ordering per applicant.** Each event is its own job with backoff. If `status.changed: submitted` fails once and waits 60 s, `status.changed: for_exam` can arrive first. If the DMS keeps "the latest event" as the current status, it will be wrong. Either the DMS orders by `occurred_at`, which should be agreed in the contract, or delivery holds later events while an earlier one for the same `applicant_ref` is pending.
- **Endless retries.** [retryOutstanding](../app/Services/Dms/DmsSync.php#L81) re-queues **every failed event every hour** (scheduled `dms:outbox --retry`), each with 6 more tries. An event the DMS always rejects (a 422 on its payload) retries forever and fills the logs. Add a cap (e.g. stop automatic retries after 30 attempts and leave it for `--retry` by hand).
- **Duplicate jobs.** Line 84 also re-queues pending events last touched more than an hour ago. An event in its final 3,600 s backoff matches, so two jobs for the same event can run at the same time. The idempotency key protects the DMS, but `attempts` double-counts. Make the job `ShouldBeUnique` (unique ID = event ID).

### 10. Notifications queued inside transactions

`ApplicationStatusChanged` is dispatched inside `transition()`, which usually runs inside a `DB::transaction`. The listener queues `AdmissionNotice` (`ShouldQueue`), and [config/queue.php](../config/queue.php#L44) has `after_commit => false`. On the **database** queue this happens to be safe, because the job row is inserted in the same transaction and rolls back with it. On **Redis or SQS** (a likely AWS move), an applicant could receive "Application received" for a submission that rolled back, or the job could run before the data is committed.

**Fix:** set `'after_commit' => true` on the queue connections (the outbox job already uses `->afterCommit()`), or add `public $afterCommit = true;` to the queued notifications.

---

## Low

### 11. Smaller N+1 queries and repeated lookups

- `verifyPending`: `$d->requirement->key` in the loop ([DocumentService.php:205](../app/Services/DocumentService.php#L205)), and `submitMedical`: `$d->requirement->stage` ([line 133](../app/Services/DocumentService.php#L133)). Add `->with('requirement')`.
- `AdmissionPeriod::current()` runs a query every time it's called (presenters, services, reports), several times in one request. Memoize it for the request (e.g. `once()`).
- `ReportService::dailySubmissions` loads every `submitted_at` into PHP to group by day. A `GROUP BY DATE(CONVERT_TZ(...))` on MySQL scales better. Fine at demo size.
- PDFs (permit, form, letter) are rendered with DomPDF on every download, and the photo is fetched from the store (the DMS in production) each time. Cache them by `application.updated_at` if exam week brings heavy permit printing.

### 12. The duplicate-applicant check cannot use its index

[ApplicationService::flagDuplicate](../app/Services/ApplicationService.php#L297) uses `LOWER(last_name) = ?`, so the new `(last_name, first_name, birthday)` index can't be used, and every name or birthday change scans the applicants table. On MySQL the default `_ci` collation already compares case-insensitively, so plain `where('last_name', $value)` gives the same result and uses the index. Keep `LOWER()` only for SQLite, or store a normalized key column.

### 13. Data retention and growth

The scheduler prunes only failed jobs and batches. Nothing removes:
- `dms_outbox` rows already `sent` (each holds a full applicant record as JSON),
- read `notifications`, expired `trusted_devices`, and Pulse data beyond its own trim,
- files of removed or replaced documents. [remove()](../app/Services/DocumentService.php) only clears `is_current`, and if the DB transaction in `upload()` fails after the file is stored, the file is orphaned.

D10 (retention policy) is still open in PROGRESS.md. Once it's decided, add `model:prune` / scheduled clean-up commands. Until then, at least prune `sent` outbox rows older than 90 days and expired trusted devices.

### 14. Robustness

- **Config validation:** `DOCUMENT_STORE=dms` or `DMS_DRIVER=http` with an empty `DMS_BASE_URL` fails only at the first upload, as a 500. Check required settings at boot (or in `app:install` / a `config:check` command) and fail with a clear message.
- **Login timing:** [AuthController::login](../app/Http/Controllers/Api/AuthController.php) skips the bcrypt check when the email doesn't exist, so response time reveals which emails are registered. Low impact (registration reveals it too), but cheap to fix by always running `Hash::check` against a dummy hash.
- **AuditLogger** takes the `Request` in its constructor. That's fine on PHP-FPM, but it records a stale IP and agent under Octane or in long-running workers. Resolve `request()` inside `record()` instead.

---

## Not done in this session (on purpose)

- **No application code changed.** Every finding touches files the other session currently has open with uncommitted changes. Editing them here would cause merge conflicts.
- **Laravel Boost** (CLAUDE.md asks for `boost:install`): `laravel/boost` is already in `require-dev` in the uncommitted `composer.json`. Running the installer here would rewrite files in that working tree, so it's left for that session.
- **No load test** was run. The PROGRESS.md figures (2026-10-17) are still the latest measurements. After fixes 5–7, re-run `php artisan db:seed --class=LoadTestSeeder` and compare office sign-in time.

## Suggested order

1. Fix the tests (4) so the suite is green and can catch the next regressions.
2. Fix 1–3 (correctness under concurrency and across periods) with a test each.
3. Add the index migration (5) and the audit limit (6): small changes with a large effect on the SQLite demo.
4. Fix 7–10 before the DMS integration goes live.
5. Do 11–14 when convenient.
