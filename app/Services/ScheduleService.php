<?php
namespace App\Services;
use App\Models\Employee;
use App\Models\WorkSchedule;
use App\Models\AttendanceRecord;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
class ScheduleService {
 public function forDate(Employee $e,string $date):?WorkSchedule{return $e->schedules()->whereDate('effective_from','<=',$date)->where(fn($q)=>$q->whereNull('effective_to')->orWhereDate('effective_to','>=',$date))->orderByDesc('effective_from')->first();}
 public function local(string $date,string $time):Carbon{return Carbon::parse($date.' '.$time,'Asia/Manila');}
 public function scheduled(WorkSchedule $s,string $date):bool{return in_array(Carbon::parse($date)->dayOfWeekIso,$s->work_days??[1,2,3,4,5],true)&&!in_array($date,Policy::get()['holidays']??[],true);}
 public function segments(WorkSchedule $s,string $date):array{
  if($s->lunch_start&&$s->lunch_end)return [['am_in','am_out',$this->local($date,$s->work_start),$this->local($date,$s->lunch_start)],['pm_in','pm_out',$this->local($date,$s->lunch_end),$this->local($date,$s->work_end)]];
  return [['am_in','am_out',$this->local($date,$s->work_start),$this->local($date,$s->work_end)]];
 }
 public function minutes(WorkSchedule $s,string $date,?AttendanceRecord $r):array{
  $scheduled=0;$worked=0;$actual=0;$late=0;$missing=[];
  foreach($this->segments($s,$date) as [$in,$out,$start,$end]){
   $duration=intdiv($end->timestamp-$start->timestamp,60);$scheduled+=$duration;
   if(!$r||!$r->$in||!$r->$out){if($r && (bool)$r->$in !== (bool)$r->$out)$missing[]=$in.'/'.$out;continue;}
   $actualIn=$r->$in->copy()->setTimezone('Asia/Manila');$actualOut=$r->$out->copy()->setTimezone('Asia/Manila');
   $a=max($actualIn->timestamp,$start->timestamp);$b=min($actualOut->timestamp,$end->timestamp);
   $delay=max(0,intdiv($actualIn->timestamp-$start->timestamp,60));
   $allowed=min($delay,(int)$s->grace_minutes);
   $span=max(0,intdiv($b-$a,60));$actual+=$span;$worked+=min($duration,$span+$allowed);$late+=max(0,$delay-$allowed);
  }
  return ['scheduled_minutes'=>$scheduled,'worked_minutes'=>min($scheduled,$actual),'credited_minutes'=>min($scheduled,$worked),'late_minutes'=>$late,'uncovered_minutes'=>max(0,$scheduled-$worked),'incomplete'=>$missing];
 }
 public function summary(Employee $e,string $date,?AttendanceRecord $record):array {
  $schedule=$this->forDate($e,$date);if(!$schedule)return ['late_minutes'=>0,'worked_minutes'=>0,'uncovered_minutes'=>0,'scheduled_minutes'=>0,'status_code'=>'incomplete','label'=>'No assigned schedule'];
  $m=$this->minutes($schedule,$date,$record);$today=now()->timezone('Asia/Manila')->toDateString();
  if($m['incomplete'])$code=$date===$today?'in_progress':'incomplete';
  elseif(!$m['worked_minutes'])$code='incomplete';
  elseif($m['worked_minutes']<$m['scheduled_minutes']/2+1&&$m['uncovered_minutes']>0)$code='partial';
  elseif($m['late_minutes']>0)$code='late';
  elseif($m['uncovered_minutes']>0)$code='early_out';
  else $code='completed';
  return $m+['status_code'=>$code,'label'=>['in_progress'=>'In progress','incomplete'=>'Incomplete','partial'=>'Partial day','late'=>'Late','early_out'=>'Early Out','completed'=>'Completed'][$code]];
 }
 public function validate(array $v):void{
  if($v['work_end']<=$v['work_start'])throw ValidationException::withMessages(['work_end'=>'The workday must end after it starts. Overnight shifts require a separately agreed policy.']);
  if((bool)($v['lunch_start']??null)!==(bool)($v['lunch_end']??null))throw ValidationException::withMessages(['lunch_start'=>'Supply both lunch boundaries or neither.']);
  if(!empty($v['lunch_start'])&&!($v['work_start']<$v['lunch_start']&&$v['lunch_start']<$v['lunch_end']&&$v['lunch_end']<$v['work_end']))throw ValidationException::withMessages(['lunch_start'=>'Lunch must fall within the assigned workday.']);
 }
 public function save(Employee $e,array $v):WorkSchedule{
  $this->validate($v);$s=WorkSchedule::updateOrCreate(['employee_id'=>$e->id,'effective_from'=>$v['effective_from']],$v);Audit::record('schedule_saved','work_schedules',(string)$s->id,['employee_no'=>$e->employee_no]);return $s;
 }
}
