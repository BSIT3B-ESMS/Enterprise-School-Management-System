<?php
namespace App\Http\Middleware;
use Closure;
use App\Models\IntegrationToken;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
class AppToken {
    public function handle(Request $request, Closure $next, string $resource, string $action = '') {
        // Laravel middleware parameters are comma-delimited, not colon-delimited.
        $permission = $action === '' ? $resource : $resource.':'.$action;
        $started = microtime(true); $caller = 'unauthenticated';
        try {
            if (!$request->isSecure() && !in_array($request->ip(),['127.0.0.1','::1'],true)) {
                $response = ApiResponse::error('HTTPS is required.',400,['code'=>'https_required']);
            } else {
                $bearer = $request->bearerToken();
                $token = $bearer ? IntegrationToken::where('token_hash',hash('sha256',$bearer))->whereNull('revoked_at')->first() : null;
                if (!$token) {
                    $response = ApiResponse::error('Unauthenticated.',401)->header('WWW-Authenticate','Bearer');
                } else {
                    $caller = $token->caller; $request->attributes->set('integration_token',$token);
                    $key = 'integration:'.$token->id;
                    if (RateLimiter::tooManyAttempts($key,60)) {
                        $response = ApiResponse::error('Too many requests.',429)->header('Retry-After',(string) RateLimiter::availableIn($key));
                    } else {
                        RateLimiter::hit($key,60); $token->forceFill(['last_used_at'=>now()])->save();
                        $response = in_array($permission,$token->permissions,true)
                            ? $next($request) : ApiResponse::error('Forbidden.',403);
                    }
                }
            }
        } catch (\Throwable $e) {
            $response = app(\Illuminate\Contracts\Debug\ExceptionHandler::class)->render($request,$e);
        }
        $requestId = $request->header('X-Request-Id');
        $requestId = is_string($requestId) && preg_match('/^[A-Za-z0-9-]{1,64}$/',$requestId) ? $requestId : (string) Str::uuid();
        $response->headers->set('X-Request-Id',$requestId);
        // Route template, not query parameters, request bodies, credentials or file names.
        Log::info('integration_request',['caller'=>$caller,'method'=>$request->method(),'path'=>$request->route()?->uri(),
            'status'=>$response->getStatusCode(),'duration_ms'=>(int)((microtime(true)-$started)*1000),'request_id'=>$requestId,'at'=>now()->utc()->toIso8601ZuluString()]);
        return $response;
    }
}
