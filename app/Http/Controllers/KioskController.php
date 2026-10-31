<?php
namespace App\Http\Controllers;

use App\Models\{Employee,KioskDevice};
use App\Services\{AttendanceService,Audit};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KioskController extends Controller {
 public function index(Request $r){
  $device=$r->session()->has('kiosk_device_id')?KioskDevice::whereKey($r->session()->get('kiosk_device_id'))->whereNull('revoked_at')->first():null;
  return view('app.kiosk',compact('device'));
 }
 /** Admin signs in normally, then authorizes the browser in place; no employee pairing prompt. */
 public function authorizeBrowser(Request $r){
  abort_unless($r->user()?->role==='admin' && $r->session()->get('mfa_verified'),403);
  $v=$r->validate(['name'=>'nullable|string|max:100']);
  $device=KioskDevice::create(['name'=>$v['name']?:'Attendance kiosk '.now()->format('Y-m-d H:i'),'token_hash'=>hash('sha256',random_bytes(32))]);
  Audit::record('kiosk_browser_authorized','kiosk_devices',(string)$device->id);
  // Exit the administrator session before leaving an unattended employee-facing kiosk.
  \Illuminate\Support\Facades\Auth::logout();
  $r->session()->invalidate();$r->session()->regenerateToken();
  $r->session()->put('kiosk_device_id',$device->id);
  return redirect('/kiosk')->with('success','This browser is authorized for attendance scanning. Administrator signed out safely.');
 }
 /** Existing one-time pairing remains available to support remote authorized terminals. */
 public function pair(Request $r){
  $v=$r->validate(['device_token'=>'required|string|max:128']);
  $d=KioskDevice::where('token_hash',hash('sha256',$v['device_token']))->whereNull('revoked_at')->first();
  if(!$d)return back()->withErrors(['device_token'=>'Invalid or revoked device token.']);
  $r->session()->put('kiosk_device_id',$d->id);$r->session()->regenerate();
  return redirect('/kiosk')->with('success','Kiosk authorized.');
 }
 public function unpair(Request $r){$r->session()->forget('kiosk_device_id');return redirect('/kiosk');}
 public function punch(Request $r){
  $d=KioskDevice::whereKey($r->session()->get('kiosk_device_id'))->whereNull('revoked_at')->first();
  abort_unless($d,403,'An administrator must authorize this kiosk browser first.');
  $v=$r->validate(['card_uid'=>'required|string|max:190','client_key'=>'required|uuid','photo'=>'required|image|mimes:jpg,jpeg,png,webp|max:2048']);
  $card=strtoupper(preg_replace('/[^A-Za-z0-9]/','',$v['card_uid']));
  $e=Employee::where('rfid_uid',$v['card_uid'])->orWhere('rfid_uid',$card)->first();
  if(!$e)throw ValidationException::withMessages(['card_uid'=>'Card is not registered. Ask HR to register this card.']);
  $service=app(AttendanceService::class);
  // A repeat with the same idempotency key must return its original event.
  $existing=\App\Models\AttendancePunch::where('client_key',$v['client_key'])->first();
  $event=$existing?->event_type??$service->nextEvent($e);
  $p=$service->punch($e,$event,$v['client_key'],$r->file('photo'),$d);
  $schedule=app(\App\Services\ScheduleService::class)->forDate($e,$p->work_date);
  $scheduledStart=$schedule?app(\App\Services\ScheduleService::class)->local($p->work_date,$p->event_type==='pm_in'?$schedule->lunch_end:$schedule->work_start):null;
  $localTime=$p->occurred_at->copy()->timezone('Asia/Manila');
  $lateMinutes=$scheduledStart?max(0,(int)$scheduledStart->diffInMinutes($localTime,false)):0;
  $label=match($p->event_type){
   'am_in','pm_in'=>$lateMinutes>(int)($schedule?->grace_minutes??0)?'Late ('.$lateMinutes.'m)':($p->event_type==='am_in'?'On-Time':'In progress'),
   'am_out'=>'Lunch Break',
   default=>$schedule&&$localTime->lt(app(\App\Services\ScheduleService::class)->local($p->work_date,$schedule->work_end))?'Early Out':'Completed',
  };
  $result=['data'=>['status_label'=>$label,'employee_no'=>$e->employee_no,'employee_name'=>$e->full_name,'position'=>$e->position,'event_type'=>$p->event_type,'occurred_at'=>$p->occurred_at->copy()->utc()->toIso8601ZuluString(),'display_time'=>$p->occurred_at->timezone('Asia/Manila')->format('g:i A'),'work_date'=>$p->work_date],'meta'=>['saved'=>true]];
  if($r->expectsJson())return response()->json($result);
  return back()->with('success',$e->full_name.' — '.strtoupper(str_replace('_',' ',$p->event_type)).' recorded at '.$result['data']['display_time'].'.');
 }
}
