<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class NotificationController extends Controller {
 public function index(){$notifications=DB::table('portal_notifications')->where('user_id',auth()->id())->orderByDesc('id')->paginate(20);return view('notifications',compact('notifications'));}
 public function read(int $id){$notice=DB::table('portal_notifications')->where(['id'=>$id,'user_id'=>auth()->id()])->first();abort_unless($notice,404);DB::table('portal_notifications')->where('id',$id)->update(['read_at'=>now(),'updated_at'=>now()]);return redirect($notice->url);}
}