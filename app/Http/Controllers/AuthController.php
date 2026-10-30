<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate(['email'=>'required|email','password'=>'required|string']);
        if (!Auth::attempt($credentials+['active'=>true])) {
            throw ValidationException::withMessages(['email'=>'The supplied credentials are invalid.']);
        }
        $request->session()->regenerate();
        app(\App\Services\AcademicService::class)->audit('login','users',auth()->id());return redirect()->intended(route('dashboard'));
    }
    public function logout(Request $request)
    {
        app(\App\Services\AcademicService::class)->audit('logout','users',auth()->id());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
    public function password(Request $request)
    {
        $data = $request->validate(['current_password'=>'required|current_password','password'=>['required','confirmed',Password::min(12)->mixedCase()->numbers()]]);
        $request->user()->forceFill(['password'=>Hash::make($data['password']),'must_change_password'=>false])->save();
        $request->session()->regenerate();
        \Illuminate\Support\Facades\DB::table('sessions')->where('user_id',$request->user()->id)->where('id','!=',$request->session()->getId())->delete();$request->user()->tokens()->delete();app(\App\Services\NotificationService::class)->accountEvent($request->user()->id,'Password changed');
        app(\App\Services\AcademicService::class)->audit('password_changed','users',$request->user()->id);
        return redirect()->route('dashboard')->with('status','Your password has been updated.');
    }
}