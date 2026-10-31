<?php
namespace App\Services;
use App\Models\{Employee,AttendanceRecord,AttendancePunch,KioskDevice,Payslip,PayrollPeriod};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class AttendanceService {
 /** Resolve the next permitted event from the effective employee schedule and saved punches.
  * The browser never chooses an event; server time in Asia/Manila is authoritative.
  * Missing morning attendance does not prevent a valid afternoon IN. */
 public function nextEvent(Employee $employee,?\Illuminate\Support\Carbon $instant=null):string {
  if(!$employee->eligible())throw ValidationException::withMessages(['card_uid'=>'Employee is inactive.']);
  $local=($instant??now())->copy()->timezone('Asia/Manila');
  $date=$local->toDateString();$schedule=app(ScheduleService::class)->forDate($employee,$date);
  $ss=app(ScheduleService::class);
  if(!$schedule||!$ss->scheduled($schedule,$date))throw ValidationException::withMessages(['card_uid'=>'No assigned working schedule for today.']);
  $rec=AttendanceRecord::where('employee_id',$employee->id)->where('work_date',$date)->first();
  $prior=AttendancePunch::where('employee_id',$employee->id)->where('work_date',$date)->orderByDesc('occurred_at')->first();
  if($prior&&$prior->occurred_at->greaterThan($local->copy()->utc()->subSeconds(60)))throw ValidationException::withMessages(['card_uid'=>'Wait 1 minute before another tap.']);
  $start=$ss->local($date,$schedule->work_start);
  $end=$ss->local($date,$schedule->work_end);
  $inEarly=$start->copy()->subMinutes((int)$schedule->early_clock_in_minutes);
  if($local->lt($inEarly))throw ValidationException::withMessages(['card_uid'=>'Too early. Your first attendance window opens at '.$inEarly->format('g:i A').'.']);
  if($local->gt($end->copy()->addHours(4)))throw ValidationException::withMessages(['card_uid'=>'Your attendance window has ended. Submit an attendance correction.']);
  if(!$schedule->lunch_start||!$schedule->lunch_end){
   if($rec?->am_out)throw ValidationException::withMessages(['card_uid'=>'Attendance is complete for today.']);
   if($rec?->am_in)return 'am_out';
   return 'am_in';
  }
  $ls=$ss->local($date,$schedule->lunch_start);$le=$ss->local($date,$schedule->lunch_end);
  $early=max(0,(int)$schedule->lunch_window_before_minutes);
  // The original four-punch workflow permits a PM-only arrival when AM was missed.
  if($rec?->pm_out)throw ValidationException::withMessages(['card_uid'=>'Attendance is complete for today.']);
  if($rec?->pm_in)return 'pm_out';
  if($local->gte($le->copy()->subMinutes($early)))return 'pm_in';
  if($local->betweenIncluded($ls->copy()->subMinutes($early),$ls->copy()->addMinutes((int)$schedule->lunch_window_after_minutes))){
   if($rec?->am_in&&!$rec?->am_out)return 'am_out';
   throw ValidationException::withMessages(['card_uid'=>'Morning IN was missed, or morning OUT is already recorded. File an attendance correction if needed.']);
  }
  if(!$rec?->am_in&&!$rec?->am_out&&$local->lt($ls->copy()->subMinutes($early)))return 'am_in';
  throw ValidationException::withMessages(['card_uid'=>'No tap available in this time window. File an attendance correction for missing taps.']);
 }
 public function punch(Employee $employee,string $event,string $clientKey,$photo,?KioskDevice $device):AttendancePunch{
  if(!$employee->eligible())throw ValidationException::withMessages(['card'=>'Employee is inactive.']);
  $prior=AttendancePunch::where('client_key',$clientKey)->first();
  if($prior){if($prior->employee_id!==$employee->id||$prior->event_type!==$event||$prior->kiosk_device_id!==$device?->id)abort(409,'Request ID already belongs to another punch.');return $prior;}
  $now=now()->utc();$date=$now->copy()->timezone('Asia/Manila')->toDateString();$ss=app(ScheduleService::class);$s=$ss->forDate($employee,$date);
  if(!$s||!$ss->scheduled($s,$date))throw ValidationException::withMessages(['card'=>'No assigned working schedule for today.']);
  if(\App\Models\ApprovedLeave::where('employee_id',$employee->id)->where('status','approved')->where('starts_on','<=',$date)->where('ends_on','>=',$date)->exists())throw ValidationException::withMessages(['card'=>'Approved leave overlaps today. Ask HR/EMS to review the leave before recording work.']);
  $period=PayrollPeriod::where('starts_on','<=',$date)->where('ends_on','>=',$date)->first();if($period&&Payslip::where('period_id',$period->period_id)->where('employee_no',$employee->employee_no)->where('status','released')->exists())throw ValidationException::withMessages(['card'=>'Attendance for this cut-off is already released.']);
  $local=$now->copy()->timezone('Asia/Manila');$start=$ss->local($date,$s->work_start);$end=$ss->local($date,$s->work_end);
  if($local->lt($start->copy()->subMinutes($s->early_clock_in_minutes))||$local->gt($end->copy()->addHours(4)))throw ValidationException::withMessages(['card'=>'Outside the permitted attendance window.']);
  if(!$s->lunch_start&&in_array($event,['pm_in','pm_out'],true))throw ValidationException::withMessages(['event_type'=>'This schedule uses one IN/OUT pair.']);
  if($s->lunch_start){
   if($event==='am_in'&&$local->gte($ss->local($date,$s->lunch_start)))throw ValidationException::withMessages(['event_type'=>'The morning attendance window has ended. Select PM IN when appropriate.']);
   if($event==='am_out'&&$local->gt($ss->local($date,$s->lunch_end)))throw ValidationException::withMessages(['event_type'=>'Use an attendance correction for the missed morning OUT.']);
   if(in_array($event,['pm_in','pm_out'],true)&&$local->lt($ss->local($date,$s->lunch_end)->subMinutes($s->lunch_window_before_minutes)))throw ValidationException::withMessages(['event_type'=>'The afternoon attendance window has not started.']);
  }
  $path=$photo->store('attendance-proofs','local');
  try{return DB::transaction(function()use($employee,$event,$clientKey,$device,$now,$date,$path){
   AttendanceRecord::firstOrCreate(['employee_id'=>$employee->id,'work_date'=>$date]);$rec=AttendanceRecord::where('employee_id',$employee->id)->where('work_date',$date)->lockForUpdate()->firstOrFail();
   if($rec->$event)throw ValidationException::withMessages(['event_type'=>'This attendance event is already recorded.']);
   $paired=['am_out'=>'am_in','pm_out'=>'pm_in'];if(isset($paired[$event])&&!$rec->{$paired[$event]})throw ValidationException::withMessages(['event_type'=>'Record the matching IN first, or file an attendance correction.']);
   $rec->$event=$now;$rec->source='RFID/NFC';$rec->status='Recorded';$rec->save();
   $p=AttendancePunch::create(['punch_id'=>(string)Str::uuid(),'employee_id'=>$employee->id,'kiosk_device_id'=>$device?->id,'work_date'=>$date,'event_type'=>$event,'occurred_at'=>$now,'photo_path'=>$path,'client_key'=>$clientKey]);
   $device?->update(['last_used_at'=>now()]);Audit::record('attendance_punch','attendance_punches',$p->punch_id,['employee_no'=>$employee->employee_no,'event_type'=>$event]);return $p;
  });}catch(\Throwable $e){Storage::disk('local')->delete($path);throw $e;}
 }
}
