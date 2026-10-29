<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a new hire who has not yet given their emergency contact and government and tax IDs to the setup page. Accounts
 * without an employee record (the HR Admins) have nothing to fill in and are never held here.
 */
class RequireOnboarding
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->employee_id !== null && $user->onboarding_completed_at === null) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
