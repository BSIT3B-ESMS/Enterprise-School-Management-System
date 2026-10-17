<?php

namespace App\Services\Documents;

use App\Models\Application;
use App\Models\Requirement;
use Illuminate\Http\UploadedFile;

/**
 * Where uploaded documents live (decision T5). The application only keeps
 * the returned reference: a private-disk path today, a DMS document ID once
 * the Document Management System is connected. Switch with DOCUMENT_STORE.
 */
interface DocumentStore
{
    public function driver(): string;

    public function put(UploadedFile $file, Application $application, Requirement $requirement): StoredFile;

    /** @return resource */
    public function readStream(string $ref);
}
