<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class RequireActiveAccount {
 public function handle(Request $request,Closure $next){if($request->user() && !$request->user()->active){if($request->expectsJson())return response()->json(['message'=>'This account is inactive.'],403);Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect('/login')->withErrors(['email'=>'This account is inactive. Contact the Registrar.']);}return $next($request);}
}