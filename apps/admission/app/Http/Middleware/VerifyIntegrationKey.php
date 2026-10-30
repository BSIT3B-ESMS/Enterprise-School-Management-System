<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Other ERP systems authenticate with X-API-Key (paper §7.2). Usage:
 * ->middleware('integration:enrollment') accepts only the active API client
 * named "enrollment", from one of its allowed IPs (Nginx filters too).
 * Every inbound call is audited (FR-15).
 */
class VerifyIntegrationKey
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Request $request, Closure $next, string $client): Response
    {
        $key = (string) $request->header('X-API-Key');
        $caller = $key === '' ? null : ApiClient::where('key_hash', ApiClient::hashKey($key))->first();

        if (! $caller || $caller->name !== $client || ! $caller->is_active || ! $caller->allowsIp($request->ip())) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }
        // NFR-05: keys are rotated every 90 days (api-client:create again)
        if ($caller->expires_at?->isPast()) {
            return response()->json(['message' => 'This API key has expired. Ask the system administrator for a new one.'], 401);
        }

        $caller->forceFill(['last_used_at' => now()])->save();
        $response = $next($request);
        $this->audit->record('api.inbound', "API call from {$caller->name}: {$request->method()} /{$request->path()} → {$response->getStatusCode()}", $caller, actorName: "API · {$caller->name}");

        return $response;
    }
}
