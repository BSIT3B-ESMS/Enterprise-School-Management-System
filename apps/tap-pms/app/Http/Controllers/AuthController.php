<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Services\Audit;
use App\Services\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
class AuthController extends Controller {
 public function loginPage(){if(!User::where('role','admin')->exists())return redirect('/setup');return auth()->check()?redirect('/'):view('auth.login');}
 public function setupPage(){abort_if(User::where('role','admin')->exists(),404);return view('auth.setup');}
 public function setup(Request $r){
  $v=$r->validate(['name'=>'required|string|max:120','admin_id'=>['required','string','max:50','regex:/^ADMIN-[A-Za-z0-9_-]+$/','unique:users,login_id'],'password'=>['required','confirmed',Password::min(12)->letters()->numbers()]]);
  $u=DB::transaction(function()use($v){
   DB::table('application_settings')->insertOrIgnore(['key'=>'setup_lock','value'=>'null','created_at'=>now(),'updated_at'=>now()]);
   DB::table('application_settings')->where('key','setup_lock')->lockForUpdate()->first();
   abort_if(User::where('role','admin')->exists(),403);
   return User::create(['name'=>$v['name'],'login_id'=>$v['admin_id'],'password'=>$v['password'],'role'=>'admin']);
  });
  Auth::login($u);$r->session()->regenerate();$r->session()->put(['auth_password_hash'=>$u->password,'auth_password_user_id'=>$u->id]);
  Audit::record('admin_created','users',(string)$u->id);return redirect()->route('mfa.show');
 }
 public function login(Request $r){
  $v=$r->validate(['identifier'=>'required|string|max:190','password'=>'required|string']);
  $credentials=['login_id'=>$v['identifier'],'password'=>$v['password']];
  $authenticated=Auth::attempt($credentials);
  // Existing v6 accounts may still use their former email until they learn their assigned ID.
  if(!$authenticated&&filter_var($v['identifier'],FILTER_VALIDATE_EMAIL)){
   $authenticated=Auth::attempt(['email'=>$v['identifier'],'password'=>$v['password']]);
  }
  if(!$authenticated)return back()->withErrors(['identifier'=>'The ID or password is incorrect.'])->onlyInput('identifier');
  $u=Auth::user();
  if(!$u->is_active||($u->employee&&!$u->employee->eligible())){
   Auth::logout();return back()->withErrors(['identifier'=>'This account is inactive.']);
  }
  $r->session()->regenerate();$r->session()->put(['auth_password_hash'=>$u->password,'auth_password_user_id'=>$u->id]);
  $r->session()->forget('mfa_verified');Audit::record('login','users',(string)$u->id);
  return $u->role==='admin'?redirect()->route('mfa.show'):redirect('/');
 }
 public function mfa(Request $r){
  $u=$r->user();abort_unless($u->role==='admin',403);if($r->session()->get('mfa_verified'))return redirect('/');
  try{$secret=$u->totp_secret;}catch(\Illuminate\Contracts\Encryption\DecryptException $e){return response()->view('auth.mfa-unavailable',[],503);}
  if(!$secret){$u->totp_secret=Totp::secret();$u->save();}
  return view('auth.mfa',['enrolling'=>!$u->totp_confirmed_at,'secret'=>$u->totp_confirmed_at?null:$u->totp_secret]);
 }
 public function verifyMfa(Request $r){
  $r->validate(['code'=>'required|string|max:80']);$u=$r->user();abort_unless($u->role==='admin' && $u->totp_secret,403);
  $ok=DB::transaction(function()use($u,$r){$u=User::lockForUpdate()->findOrFail($u->id);$counter=Totp::counter($u->totp_secret,$r->code,$u->totp_last_counter);if($counter!==null){$u->totp_last_counter=$counter;$u->totp_confirmed_at??=now();$u->save();return true;}
   $codes=$u->recovery_codes??[];foreach($codes as $i=>$hash)if(Hash::check($r->code,$hash)){unset($codes[$i]);$u->recovery_codes=array_values($codes);$u->save();return true;}return false;});
  if(!$ok)return back()->withErrors(['code'=>'Invalid or already-used authenticator code.']);
  $r->session()->put('mfa_verified',true);$r->session()->regenerate();$u->refresh();
  if(!$u->recovery_codes){$plain=[];for($i=0;$i<8;$i++)$plain[]=bin2hex(random_bytes(8));$u->recovery_codes=array_map(fn($c)=>Hash::make($c),$plain);$u->save();$r->session()->flash('recovery_codes',$plain);}
  Audit::record('mfa_verified','users',(string)$u->id);return redirect('/');
 }
 public function logout(Request $r){Audit::record('logout','users',(string)auth()->id());Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/login');}
}
