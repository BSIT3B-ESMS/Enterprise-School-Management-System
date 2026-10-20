<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HR Head and HR Staff must have a confirmed authenticator app before using any other page (config ems.require_mfa).
 */
class RequireTwoFactorForHr
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (config('ems.require_mfa') && $user?->isHr() && $user->two_factor_confirmed_at === null) {
            return redirect()->route('two-factor.setup')
                ->with('warning', 'Set up your authenticator app to continue. HR accounts require two-factor authentication.');
        }

        return $next($request);
    }
}
