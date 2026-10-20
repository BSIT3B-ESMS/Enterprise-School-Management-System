<?php

namespace App\Http\Middleware;

use App\Models\ApiConsumer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs in an external system by its bearer token and checks it holds the ability a route needs. Every answer is
 * logged with its route and status, never the token or the payload.
 */
class AuthenticateApiConsumer
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $token = $request->bearerToken();
        $consumer = $token ? ApiConsumer::query()->where('token_hash', ApiConsumer::hashToken($token))->where('is_active', true)->first() : null;

        if (! $consumer) {
            return response()->json(['message' => 'Unauthenticated.'], 401)->header('WWW-Authenticate', 'Bearer');
        }

        if (! $consumer->can($ability)) {
            return $this->log($consumer, $request, response()->json(['message' => 'This token is not allowed to read this.'], 403));
        }

        $request->attributes->set('api_consumer', $consumer);
        $consumer->forceFill(['last_used_at' => now()])->saveQuietly();

        return $this->log($consumer, $request, $next($request));
    }

    private function log(ApiConsumer $consumer, Request $request, Response $response): Response
    {
        $consumer->accessLogs()->create([
            'route' => mb_substr($request->path(), 0, 120),
            'status' => $response->getStatusCode(),
            'ip_address' => $request->ip(),
        ]);

        return $response;
    }
}
