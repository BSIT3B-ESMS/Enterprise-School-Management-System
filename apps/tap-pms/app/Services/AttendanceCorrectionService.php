<?php
namespace App\Services;
use App\Models\{Employee,AttendanceRecord,WorkflowRequest};
use Illuminate\Validation\ValidationException;
class AttendanceCorrectionService {
 public function validate(Employee $e,string $date,array $incoming,?AttendanceRecord $record=null):array {
  $today=now()->timezone('Asia/Manila');if($date>$today->toDateString())throw ValidationException::withMessages(['work_date'=>'Attendance corrections cannot be in the future.']);
  $record??=AttendanceRecord::where('employee_id',$e->id)->where('work_date',$date)->first();$merged=[];$changes=[];
  foreach(['am_in','am_out','pm_in','pm_out']as$field){$old=$record?->$field?->copy()->timezone('Asia/Manila')->format('H:i');$value=$incoming[$field]??null;
   if($value!==null&&$value!==''){
    if($old)throw ValidationException::withMessages([$field=>'This punch is already recorded; manual requests fill missing punches only.']);
    if(!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/',$value))throw ValidationException::withMessages([$field=>'Use HH:MM.']);
    if($date===$today->toDateString()&&$value>$today->format('H:i'))throw ValidationException::withMessages([$field=>'Future punch times are not allowed.']);$changes[$field]=$value;
   }$merged[$field]=$old?:($changes[$field]??null);
  }
  if(!$changes)throw ValidationException::withMessages(['request'=>'Supply at least one missing punch.']);
  if(($merged['am_out']&&!$merged['am_in'])||($merged['pm_out']&&!$merged['pm_in']))throw ValidationException::withMessages(['request'=>'OUT requires its matching IN.']);
  $last=null;foreach($merged as$value)if($value){if($last!==null&&$value<=$last)throw ValidationException::withMessages(['request'=>'Punches must follow AM IN < AM OUT < PM IN < PM OUT.']);$last=$value;}
  return $changes;
 }
 public function apply(WorkflowRequest $q,int $actor):void {
  // Called inside the approval transaction. Lock before checking for intervening kiosk writes.
  $e=Employee::findOrFail($q->employee_id);AttendanceRecord::firstOrCreate(['employee_id'=>$e->id,'work_date'=>$q->work_date]);
  $record=AttendanceRecord::where('employee_id',$e->id)->where('work_date',$q->work_date)->lockForUpdate()->firstOrFail();
  $changes=$this->validate($e,$q->work_date,$q->payload,$record);
  foreach($changes as$field=>$value)$record->$field=app(ScheduleService::class)->local($q->work_date,$value)->utc();
  $history=$record->metadata??[];$history['corrections'][]=['request_id'=>$q->request_id,'fields'=>array_keys($changes),'reviewed_by'=>$actor,'reviewed_at'=>now()->toIso8601ZuluString()];
  $record->metadata=$history;$record->source='Approved correction';$record->status='Corrected';$record->save();
 }
}
