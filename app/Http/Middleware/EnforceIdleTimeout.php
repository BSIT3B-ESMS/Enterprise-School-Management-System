<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out a user who has been idle too long: 15 minutes for HR, 30 for other staff (config/ems.php).
 */
class EnforceIdleTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $minutes = config('ems.idle_timeout_minutes.'.($user->isHr() ? 'hr' : 'staff'));
            $lastActivity = $request->session()->get('last_activity_at');

            if ($lastActivity !== null && now()->timestamp - $lastActivity > $minutes * 60) {
                Auth::guard()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('warning', "You were signed out after {$minutes} minutes of inactivity. Please sign in again.");
            }

            $request->session()->put('last_activity_at', now()->timestamp);
        }

        return $next($request);
    }
}
