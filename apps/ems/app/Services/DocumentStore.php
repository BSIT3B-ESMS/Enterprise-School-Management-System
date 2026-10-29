<?php

namespace App\Services;

use App\Models\StoredDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * The one place uploaded documents are saved and read. Today the bytes go into the stored_documents table; a separate
 * documentation system can later replace this class (keep the method names) without touching any caller.
 */
class DocumentStore
{
    /**
     * Largest accepted upload, in kilobytes.
     */
    public const MAX_KILOBYTES = 5120;

    /**
     * File types accepted for uploads.
     *
     * @var list<string>
     */
    public const ALLOWED_EXTENSIONS = ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx'];

    public function put(Model $owner, string $kind, UploadedFile $file, ?User $uploader = null): StoredDocument
    {
        $contents = (string) file_get_contents($file->getRealPath());

        $document = new StoredDocument([
            'uuid' => (string) Str::uuid(),
            'kind' => $kind,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => strlen($contents),
            'sha256' => hash('sha256', $contents),
            'contents' => $contents,
            'uploaded_by' => $uploader?->id,
        ]);
        $document->documentable()->associate($owner);
        $document->save();

        return $document;
    }

    /**
     * The bytes of a document.
     */
    public function contents(StoredDocument $document): string
    {
        return (string) StoredDocument::query()->whereKey($document->id)->value('contents');
    }

    public function forget(StoredDocument $document): void
    {
        $document->delete();
    }
}
