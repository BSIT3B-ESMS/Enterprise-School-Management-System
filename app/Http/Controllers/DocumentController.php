<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Employee;
use App\Models\StoredDocument;
use App\Services\DocumentStore;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opens an uploaded document. HR can open any application or employee document; an applicant only their own application's; an
 * employee only their own. A PDF or image opens in the browser so HR can read it right away; anything else downloads.
 */
class DocumentController extends Controller
{
    public function show(Request $request, string $uuid, DocumentStore $store): Response
    {
        $document = StoredDocument::query()->where('uuid', $uuid)->firstOrFail();

        abort_unless($this->canOpen($request, $document), 404);

        return $this->respond($document, $store, $request->boolean('download') || ! $document->isViewable());
    }

    private function respond(StoredDocument $document, DocumentStore $store, bool $asDownload): Response
    {
        $disposition = $asDownload ? 'attachment' : 'inline';
        $safeName = str_replace(['"', "\r", "\n"], '', $document->original_name);

        return response($store->contents($document), 200, [
            'Content-Type' => $document->isViewable() ? $document->mime_type : 'application/octet-stream',
            'Content-Disposition' => "{$disposition}; filename=\"{$safeName}\"",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    private function canOpen(Request $request, StoredDocument $document): bool
    {
        $user = $request->user();

        if ($user->isHr()) {
            return true;
        }

        $owner = $document->documentable;

        return match (true) {
            $owner instanceof Application => $owner->user_id === $user->id,
            $owner instanceof Employee => $user->employee_id !== null && $owner->id === $user->employee_id,
            default => false,
        };
    }
}
