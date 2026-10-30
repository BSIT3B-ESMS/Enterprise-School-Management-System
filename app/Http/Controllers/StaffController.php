<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Services\AcademicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
class StaffController extends Controller {
 public function index(){$users=User::whereIn('role',['registrar','department_head'])->orderBy('name')->paginate(20);return view('staff',compact('users'));}
 public function store(Request $request,AcademicService $service){$data=$request->validate(['name'=>'required|string|max:255','email'=>'required|email|max:255|unique:users,email','role'=>'required|in:registrar,department_head','password'=>['required','confirmed',Password::min(12)->mixedCase()->numbers()]]);DB::transaction(function()use($data,$service){$user=new User;$user->forceFill(['name'=>$data['name'],'email'=>$data['email'],'role'=>$data['role'],'password'=>Hash::make($data['password']),'must_change_password'=>true])->save();$service->audit('staff_account_created','users',$user->id,['role'=>$data['role']]);});return back()->with('status','Staff account created. A password change will be required.');}
 public function toggle(int $id,AcademicService $service){DB::transaction(function()use($id,$service){User::where('role','registrar')->orderBy('id')->lockForUpdate()->get();if(!User::find(auth()->id())->active)AcademicService::fail('Your account is no longer active.');$user=User::whereIn('role',['registrar','department_head'])->where('id',$id)->lockForUpdate()->firstOrFail();if($user->id===auth()->id())AcademicService::fail('You cannot deactivate your own account.');$user->forceFill(['active'=>!$user->active])->save();if(!$user->active){DB::table('sessions')->where('user_id',$id)->delete();$user->tokens()->delete();}$service->audit($user->active?'staff_activated':'staff_deactivated','users',$id);});return back()->with('status','Staff account status updated.');}
}
