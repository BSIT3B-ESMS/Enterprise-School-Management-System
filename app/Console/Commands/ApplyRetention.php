<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Models\AdmissionPeriod;
use App\Models\ApplicationDocument;
use App\Services\AuditLogger;
use App\Services\Dms\DmsSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * NFR-08: documents of applicants who were not admitted (or withdrew) are
 * deleted RETENTION_MONTHS (12) after their admission period closes (its
 * last date: deadline, results or reservation). Admitted applicants' files
 * stay: their records pass to the Registrar. The document rows and the
 * audit log stay; only the files go, and purged_at records when.
 *
 * Without --apply it only reports what is due. The scheduler runs it with
 * --apply monthly only when RETENTION_ENABLED=true.
 */
class ApplyRetention extends Command
{
    protected $signature = 'app:retention {--apply : Delete the files (otherwise only report)}';

    protected $description = 'Delete documents of non-admitted applicants past the retention period (NFR-08)';

    public function handle(AuditLogger $audit, DmsSync $dms): int
    {
        $months = (int) config('admission.retention_months');
        $periods = AdmissionPeriod::where('is_active', false)->get()
            ->filter(fn (AdmissionPeriod $p) => collect([$p->application_deadline, $p->results_release_on, $p->reservation_deadline])->filter()->max()?->copy()->addMonths($months)->isPast())
            ->pluck('id');

        $kept = array_map(fn (ApplicationStatus $s) => $s->value, [ApplicationStatus::Admitted, ApplicationStatus::ForMedical, ApplicationStatus::SlotAccepted]);
        $due = ApplicationDocument::whereNull('purged_at')
            ->whereHas('application', fn ($q) => $q->withTrashed()->whereIn('admission_period_id', $periods)->whereNotIn('status', $kept))
            ->with('application')->get();

        $this->line("Retention: {$months} months after the period closes. Files due: {$due->count()}.");
        if (! $this->option('apply') || $due->isEmpty()) {
            return self::SUCCESS;
        }

        $disk = Storage::disk(config('admission.documents.local_disk'));
        foreach ($due as $document) {
            if ($document->storage_driver === 'local') {
                $disk->delete($document->storage_ref);
            } else {
                // The DMS holds the file: ask it to apply the same rule
                $dms->record('document.retention_expired', $document->application, ['dms_document_id' => $document->storage_ref]);
            }
            $document->forceFill(['purged_at' => now()])->save();
        }
        $audit->record('retention.applied', "Deleted {$due->count()} document files of applicants not admitted, {$months} months after their admission period closed", meta: ['periods' => $periods->all()], actorName: 'System');
        $this->info("Deleted {$due->count()} files.");

        return self::SUCCESS;
    }
}
