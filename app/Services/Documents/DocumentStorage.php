<?php

namespace App\Services\Documents;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Requirement;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

/**
 * New uploads go to the configured store; existing files are read from the
 * store they were saved in, so switching to the DMS doesn't strand old files.
 */
class DocumentStorage
{
    public function store(UploadedFile $file, Application $application, Requirement $requirement): StoredFile
    {
        return $this->driver(config('admission.documents.driver'))->put($file, $application, $requirement);
    }

    /** @return resource */
    public function readStream(ApplicationDocument $document)
    {
        return $this->driver($document->storage_driver)->readStream($document->storage_ref);
    }

    public function driver(string $name): DocumentStore
    {
        return match ($name) {
            'local' => new LocalDocumentStore(config('admission.documents.local_disk')),
            'dms' => new DmsDocumentStore(
                (string) config('admission.documents.dms.base_url'),
                (string) config('admission.documents.dms.api_key'),
                (int) config('admission.documents.dms.timeout'),
            ),
            default => throw new InvalidArgumentException("Unknown document store [{$name}]."),
        };
    }
}
