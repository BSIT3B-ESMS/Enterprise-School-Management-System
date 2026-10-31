<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class StaffSession {
 public function handle(Request $r,Closure $next,?string $role=null){
  if(!Auth::check())return redirect()->route('login');$u=Auth::user()->fresh(['employee']);if(!$u){Auth::logout();return redirect('/login');}Auth::setUser($u);
  if(($u->role==='employee'&&!$u->employee)||!$u->is_active||($u->employee&&!$u->employee->eligible())){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();abort(403,'This account is inactive.');}
  if($r->session()->get('auth_password_user_id')===$u->id&&$r->session()->has('auth_password_hash')&&!hash_equals($r->session()->get('auth_password_hash'),$u->password)){Auth::logout();$r->session()->invalidate();return redirect('/login');}
  $r->session()->put(['auth_password_hash'=>$u->password,'auth_password_user_id'=>$u->id]);
  if($role&&$u->role!==$role)abort(403);
  if($u->role==='admin'&&!$r->session()->get('mfa_verified')&&!$r->routeIs('mfa.*','logout'))return redirect()->route('mfa.show');
  return $next($r);
 }
}
