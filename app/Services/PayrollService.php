<?php
namespace App\Services;
use App\Models\{Employee,PayrollPeriod,Payslip,AttendanceRecord,SalaryHistory,WorkflowRequest,ApprovedLeave,PayrollAdjustment};
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class PayrollService {
 private function canonical(mixed $value):mixed{if(!is_array($value))return $value;if(!array_is_list($value))ksort($value);return array_map(fn($v)=>$this->canonical($v),$value);}
 public static function rounded(int $n,int $d):int{if($d<=0)throw new \InvalidArgumentException('Invalid rate denominator');return intdiv($n+intdiv($d,2),$d);}
 public function period(string $start):PayrollPeriod{
  $s=Carbon::parse($start);$a=$s->copy()->day($s->day<=15?1:16);$b=$s->day<=15?$s->copy()->day(15):$s->copy()->endOfMonth();
  return PayrollPeriod::firstOrCreate(['starts_on'=>$a->toDateString(),'ends_on'=>$b->toDateString()],['period_id'=>(string)Str::uuid(),'status'=>'draft']);
 }
 public function salary(Employee $e,string $date):string{
  $h=SalaryHistory::where('employee_id',$e->id)->whereDate('effective_on','<=',$date)->orderByDesc('effective_on')->first();
  if($h)return $h->monthly_rate;
  if(SalaryHistory::where('employee_id',$e->id)->exists())throw ValidationException::withMessages(['payroll'=>'Salary history does not cover '.$e->employee_no.' on '.$date.'.']);
  return $e->monthly_rate;
 }
 public function calculate(Employee $e,PayrollPeriod $p):array{
  if(!$e->eligible())throw ValidationException::withMessages(['payroll'=>'Employee is inactive.']);
  $policy=Policy::get();if(empty($policy['monthly_divisor'])||empty($policy['overtime_multiplier']))throw ValidationException::withMessages(['payroll'=>'Save and confirm the payroll policy before computing payroll.']);
  $ss=app(ScheduleService::class);$days=[];$date=Carbon::parse($p->starts_on);
  while($date->toDateString()<=$p->ends_on){$ds=$date->toDateString();$s=$ss->forDate($e,$ds);if(!$s)throw ValidationException::withMessages(['payroll'=>'Assign an effective schedule covering '.$ds.' for '.$e->employee_no.'.']);if($ss->scheduled($s,$ds))$days[]=[$ds,$s];$date->addDay();}
  if(!$days)throw ValidationException::withMessages(['payroll'=>'No scheduled workdays in the selected period.']);
  $weighted=0;$unpaid=0;$overtime=0;$daily=[];$incomplete=[];
  foreach($days as [$ds,$s]){
   $monthly=Money::cents($this->salary($e,$ds));$weighted+=$monthly;
   $record=AttendanceRecord::where('employee_id',$e->id)->where('work_date',$ds)->first();$m=$ss->minutes($s,$ds,$record);
   $leaves=ApprovedLeave::where('employee_id',$e->id)->where('status','approved')->where('starts_on','<=',$ds)->where('ends_on','>=',$ds)->get();
   if($leaves->count()>1)throw ValidationException::withMessages(['payroll'=>'Overlapping approved leave for '.$e->employee_no.' on '.$ds.'.']);
   $leave=$leaves->first();if($leave&&$m['worked_minutes']>0)throw ValidationException::withMessages(['payroll'=>'Attendance overlaps approved leave for '.$e->employee_no.' on '.$ds.'. Ask EMS to resolve the conflict.']);$uncovered=$leave&&$leave->is_paid?0:$m['uncovered_minutes'];
   $deduct=self::rounded($monthly*$uncovered,(int)$policy['monthly_divisor']*max(1,$m['scheduled_minutes']));$unpaid+=$deduct;
   $otMinutes=WorkflowRequest::where('employee_id',$e->id)->where('type','overtime')->where('status','approved')->where('work_date',$ds)->get()->sum(fn($q)=>(int)$q->payload['minutes']);
   $multiplier=Money::cents($policy['overtime_multiplier']);
   $ot=self::rounded($monthly*$otMinutes*$multiplier,(int)$policy['monthly_divisor']*max(1,$m['scheduled_minutes'])*100);$overtime+=$ot;
   if($m['incomplete']&&!$leave)$incomplete[]=$ds;
   $daily[]=['date'=>$ds,'monthly_rate'=>Money::decimal($monthly),'scheduled_minutes'=>$m['scheduled_minutes'],'worked_minutes'=>$m['worked_minutes'],'uncovered_minutes'=>$uncovered,'late_minutes'=>$m['late_minutes'],'leave_id'=>$leave?->leave_id,'leave_paid'=>$leave?->is_paid,'attendance_deduction'=>Money::decimal($deduct),'overtime_minutes'=>$otMinutes,'overtime_pay'=>Money::decimal($ot)];
  }
  $base=self::rounded($weighted,count($days)*2);$unpaid=min($base,$unpaid);
  $adjustments=PayrollAdjustment::where('employee_id',$e->id)->whereBetween('applies_on',[$p->starts_on,$p->ends_on])->orderBy('id')->get();
  $additions=0;$otherDeductions=0;$items=[];foreach($adjustments as $a){$amount=Money::cents($a->amount);if($a->kind==='addition')$additions+=$amount;else $otherDeductions+=$amount;$items[]=['adjustment_id'=>$a->adjustment_id,'kind'=>$a->kind,'label'=>$a->label,'amount'=>$a->amount];}
  $gross=$base+$overtime+$additions;
  $statutory=app(StatutoryService::class)->calculate($e,$p,Money::decimal(self::rounded($weighted,count($days))),$policy,$gross,$unpaid);
  $deductions=$unpaid+$otherDeductions+Money::cents($statutory['total']);
  if($deductions>$gross)throw ValidationException::withMessages(['payroll'=>'Deductions exceed earnings for '.$e->employee_no.'. Review approved deductions.']);
  return ['attendance_status'=>$this->attendanceStatus(['days'=>$daily,'incomplete_dates'=>$incomplete]),'employee_no'=>$e->employee_no,'employee_name'=>$e->full_name,'period_id'=>$p->period_id,'starts_on'=>$p->starts_on,'ends_on'=>$p->ends_on,'statutory'=>$statutory,'sss'=>$statutory['sss'],'gsis'=>$statutory['gsis'],'philhealth'=>$statutory['philhealth'],'pagibig'=>$statutory['pagibig'],'tax'=>$statutory['tax'],'loan'=>$statutory['loan'],'base_pay'=>Money::decimal($base),'overtime_pay'=>Money::decimal($overtime),'additions'=>Money::decimal($additions),'attendance_deduction'=>Money::decimal($unpaid),'other_deductions'=>Money::decimal($otherDeductions),'gross_pay'=>Money::decimal($gross),'deductions'=>Money::decimal($deductions),'net_pay'=>Money::decimal($gross-$deductions),'days'=>$daily,'adjustments'=>$items,'incomplete_dates'=>$incomplete,'policy'=>$policy,'computed_at'=>now()->utc()->toIso8601ZuluString()];
 }
 public function compute(PayrollPeriod $p,?int $actor):void{
  DB::transaction(function()use($p,$actor){$p=PayrollPeriod::whereKey($p->id)->lockForUpdate()->firstOrFail();
   if($p->status==='released'||Payslip::where('period_id',$p->period_id)->where('status','released')->exists())throw ValidationException::withMessages(['payroll'=>'Withdraw released payslips before recomputing this cut-off.']);
   $active=Employee::all()->filter(fn($e)=>$e->eligible());if($active->isEmpty())throw ValidationException::withMessages(['payroll'=>'Import employees from EMS first.']);
   $calculations=[];foreach($active as $e)$calculations[]=$this->calculate($e,$p);
   Payslip::where('period_id',$p->period_id)->whereNotIn('employee_no',$active->pluck('employee_no'))->delete();
   foreach($calculations as $c)Payslip::updateOrCreate(['period_id'=>$p->period_id,'employee_no'=>$c['employee_no']],['payslip_id'=>Payslip::where('period_id',$p->period_id)->where('employee_no',$c['employee_no'])->value('payslip_id')??(string)Str::uuid(),'gross_pay'=>$c['gross_pay'],'deductions'=>$c['deductions'],'net_pay'=>$c['net_pay'],'calculation'=>$c,'status'=>'draft','released_at'=>null,'released_by'=>null]);
   $p->update(['computed_at'=>now(),'computed_by'=>$actor,'status'=>'draft']);Audit::record('payroll_computed','payroll_periods',$p->period_id,['employee_count'=>count($calculations)]);
  });
 }
 public function attendanceStatus(array $calculation):string {
  $days=$calculation['days']??[];if(!$days||!empty($calculation['incomplete_dates']))return 'incomplete';
  $late=false;$early=false;$partial=false;
  foreach($days as$day){if(($day['uncovered_minutes']??0)>=($day['scheduled_minutes']??1))return 'incomplete';if(($day['uncovered_minutes']??0)>0&&($day['worked_minutes']??0)<=($day['scheduled_minutes']??0)/2)$partial=true;if(($day['late_minutes']??0)>0)$late=true;if(($day['uncovered_minutes']??0)>($day['late_minutes']??0))$early=true;}
  return $partial?'partial':($early?'early_out':($late?'late':'completed'));
 }
 public function releaseAll(PayrollPeriod $period,?int $actor):int {
  return DB::transaction(function()use($period,$actor){$period=PayrollPeriod::whereKey($period->id)->lockForUpdate()->firstOrFail();$slips=Payslip::where('period_id',$period->period_id)->where('status','draft')->orderBy('id')->lockForUpdate()->get();if($slips->isEmpty())throw ValidationException::withMessages(['payroll'=>'No draft payslips in this cut-off.']);foreach($slips as$slip)$this->release($slip,true,$actor);Audit::record('cutoff_released','payroll_periods',$period->period_id,['count'=>$slips->count()]);return $slips->count();});
 }
 public function release(Payslip $p,bool $release,?int $actor):void{
  DB::transaction(function()use($p,$release,$actor){$p=Payslip::whereKey($p->id)->lockForUpdate()->firstOrFail();$period=PayrollPeriod::where('period_id',$p->period_id)->firstOrFail();
   if($release){$e=Employee::where('employee_no',$p->employee_no)->firstOrFail();if(!$e->eligible()||!$p->calculation)throw ValidationException::withMessages(['payroll'=>'This payslip is not eligible for release.']);
    if(WorkflowRequest::where('employee_id',$e->id)->whereBetween('work_date',[$period->starts_on,$period->ends_on])->whereIn('status',['pending','pending_ems'])->exists())throw ValidationException::withMessages(['payroll'=>'Resolve this employee’s outstanding requests before release.']);
    if($p->calculation['incomplete_dates'])throw ValidationException::withMessages(['payroll'=>'Approve corrections for incomplete attendance before releasing this payslip.']);
    if(config('ems.enabled'))foreach(['employees','leaves','balances'] as $resource)if(!DB::table('ems_sync_runs')->where('status','success')->where('resource',$resource)->where('created_at','>=',now()->subHours(24))->exists())throw ValidationException::withMessages(['payroll'=>'EMS '.$resource.' data is stale. Synchronize before release.']);
    $fresh=$this->calculate($e,$period);foreach(['gross_pay','deductions','net_pay','days','adjustments','policy','statutory'] as $field)if($this->canonical($fresh[$field])!==$this->canonical($p->calculation[$field]??null))throw ValidationException::withMessages(['payroll'=>'Inputs changed after computation. Recompute this cut-off.']);
   }
   $p->update(['status'=>$release?'released':'draft','released_at'=>$release?now():null,'released_by'=>$release?$actor:null]);
   $period->update(['status'=>Payslip::where('period_id',$p->period_id)->where('status','!=','released')->exists()?'draft':'released']);
   Audit::record($release?'payslip_released':'payslip_withdrawn','payslips',$p->payslip_id);
  });
 }
}
