<?php

namespace App\Services\Documents;

use App\Models\Application;
use App\Models\Requirement;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The university Document Management System (architecture diagram:
 * "Gets uploads directly · Medical = confidential · REST API + API key").
 *
 * The DMS API isn't published yet, so the endpoints below are the assumed
 * contract (POST /documents → { id }, GET /documents/{id}/content). Adjust
 * here only when the real API is known.
 */
class DmsDocumentStore implements DocumentStore
{
    public function __construct(
        private string $baseUrl,
        private string $apiKey,
        private int $timeout,
    ) {}

    public function driver(): string
    {
        return 'dms';
    }

    public function put(UploadedFile $file, Application $application, Requirement $requirement): StoredFile
    {
        $checksum = hash_file('sha256', $file->getRealPath());

        $response = $this->client()
            // The same file for the same requirement is stored once, even if the call is retried
            ->withHeaders(['Idempotency-Key' => hash('sha256', $application->number.'|'.$requirement->key.'|'.$checksum)])
            ->attach('file', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName())
            ->post('/documents', [
                'source' => 'admission',
                'owner_ref' => $application->number,
                'category' => $requirement->key,
                'confidential' => $requirement->stage === Requirement::STAGE_MEDICAL ? '1' : '0',
                'sha256' => $checksum,
            ]);

        $id = $response->throw()->json('id');
        if (! $id) {
            throw new RuntimeException('The document system did not return a document ID.');
        }

        return new StoredFile('dms', (string) $id, $checksum);
    }

    public function readStream(string $ref)
    {
        $response = $this->client()->withOptions(['stream' => true])->get('/documents/'.rawurlencode($ref).'/content')->throw();

        return $response->toPsrResponse()->getBody()->detach();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders(['X-API-Key' => $this->apiKey])
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry(3, 500, throw: false);
    }
}
