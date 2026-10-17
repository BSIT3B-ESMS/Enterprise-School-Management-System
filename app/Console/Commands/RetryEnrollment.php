<?php

namespace App\Console\Commands;

use App\Services\EnrollmentService;
use Illuminate\Console\Command;

class RetryEnrollment extends Command
{
    protected $signature = 'enrollment:retry';

    protected $description = 'Re-queue admitted applicants not yet accepted by the Enrollment system';

    public function handle(EnrollmentService $enrollment): int
    {
        if (! $enrollment->enabled()) {
            $this->info('The Enrollment integration is switched off (ENROLLMENT_ENABLED=false).');

            return self::SUCCESS;
        }
        $this->info('Queued '.$enrollment->retryOutstanding().' hand-off(s).');

        return self::SUCCESS;
    }
}
