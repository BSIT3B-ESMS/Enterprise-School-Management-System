<?php
namespace App\Http\Middleware;
use Closure;
class RestrictProfessorRoutes {
 public function handle($request,Closure $next){
  if($request->user()?->role==='professor')abort_unless($request->is('professor','professor/*','dashboard','account/password','logout','notifications','notifications/*','activity','up'),403);
  return $next($request);
 }
}
