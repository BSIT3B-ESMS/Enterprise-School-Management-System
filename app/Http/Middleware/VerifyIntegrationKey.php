<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * External systems authenticate with X-API-Key (architecture diagram).
 * Usage: ->middleware('integration:enrollment') reads
 * config('admission.enrollment.inbound_key'). Nginx adds the IP allowlist.
 */
class VerifyIntegrationKey
{
    public function handle(Request $request, Closure $next, string $system): Response
    {
        $expected = (string) config("admission.{$system}.inbound_key");
        $given = (string) $request->header('X-API-Key');

        if (! config("admission.{$system}.enabled") || $expected === '' || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
