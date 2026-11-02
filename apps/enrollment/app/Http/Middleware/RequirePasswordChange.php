<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class RequirePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->must_change_password && !$request->routeIs('password.*','logout')) {
            return $request->expectsJson()?response()->json(['message'=>'A password change is required.'],403):redirect()->route('password.edit');
        }
        return $next($request);
    }
}