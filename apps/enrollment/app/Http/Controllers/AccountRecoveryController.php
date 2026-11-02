<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
class AccountRecoveryController extends Controller {
 public function request(Request $request){$request->validate(['email'=>'required|email']);Password::sendResetLink($request->only('email'));return $request->expectsJson()?response()->json(['message'=>'If the address has an account, password recovery instructions have been sent.']):back()->with('status','If the address has an account, password recovery instructions have been sent.');}
 public function reset(Request $request){$data=$request->validate(['token'=>'required','email'=>'required|email','password'=>['required','confirmed',PasswordRule::min(12)->mixedCase()->numbers()]]);$status=Password::reset($data,function(User $user,string $password){$user->forceFill(['password'=>Hash::make($password),'must_change_password'=>false,'remember_token'=>Str::random(60)])->save();\Illuminate\Support\Facades\DB::table('sessions')->where('user_id',$user->id)->delete();$user->tokens()->delete();event(new PasswordReset($user));app(\App\Services\NotificationService::class)->accountEvent($user->id,'Password reset completed');app(\App\Services\AcademicService::class)->audit('password_reset','users',$user->id);});if($status!==Password::PASSWORD_RESET){if($request->expectsJson())throw \Illuminate\Validation\ValidationException::withMessages(['email'=>'The recovery link is invalid or expired. Request a new link.']);return back()->withErrors(['email'=>'The recovery link is invalid or expired. Request a new link.']);}return $request->expectsJson()?response()->json(['message'=>'Password reset. Sign in using your new password.']):redirect('/login')->with('status','Password reset. Sign in using your new password.');}
}