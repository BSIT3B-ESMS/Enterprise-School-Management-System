<?php
namespace App\Services;
use App\Models\{Employee,PayrollPeriod,DeductionOverride,ContributionProfile};
use App\Support\Money;
use Illuminate\Validation\ValidationException;
class StatutoryService {
 private function rounded(int $n,int $d):int{return intdiv($n+intdiv($d,2),$d);}
 public function withholding(int $taxableCents):int {
  $tax=0;foreach(config('statutory.ph_2025.semi_monthly_tax_brackets')as[$lower,$fixed,$rate])if($taxableCents>$lower)$tax=$fixed+$this->rounded(($taxableCents-$lower)*$rate,10000);return max(0,$tax);
 }
 public function monthly(string $salary,string $scheme='sss',bool $philhealth=true,bool $pagibig=true):array {
  $m=Money::cents($salary);$p=config('statutory.ph_2025');$sss=0;$gsis=0;
  if($scheme==='sss'){$msc=min($p['sss_max_cents'],max($p['sss_min_cents'],$this->rounded($m,$p['sss_step_cents'])*$p['sss_step_cents']));$sss=$this->rounded($msc*$p['sss_employee_basis_points'],10000);}
  elseif($scheme==='gsis')$gsis=$this->rounded($m*$p['gsis_employee_basis_points'],10000);
  $ph=$philhealth?$this->rounded(min($p['philhealth_max_cents'],max($p['philhealth_min_cents'],$m))*$p['philhealth_employee_basis_points'],10000):0;
  $pg=$pagibig?$this->rounded(min($m,$p['pagibig_max_cents'])*($m<=$p['pagibig_low_salary_cents']?$p['pagibig_low_basis_points']:$p['pagibig_employee_basis_points']),10000):0;
  return ['sss'=>$sss,'gsis'=>$gsis,'philhealth'=>$ph,'pagibig'=>$pg];
 }
 public function calculate(Employee $e,PayrollPeriod $period,string $monthlyBasis,array $policy,int $grossCents,int $attendanceDeduction):array {
  $mode=$policy['deduction_mode']??'automatic_official';
  if(!in_array($mode,['automatic_official','manual'],true))throw ValidationException::withMessages(['payroll'=>'Confirm the official deduction mode in Settings before computing.']);
  $profile=null;
  if($mode==='automatic_official'){
   if($period->starts_on<config('statutory.ph_2025.effective_from'))throw ValidationException::withMessages(['payroll'=>'Automatic contribution rules do not cover this historical period. Use confirmed dated manual amounts.']);
   $profile=ContributionProfile::where('employee_id',$e->id)->where('effective_from','<=',$period->starts_on)->where(fn($q)=>$q->whereNull('effective_to')->orWhere('effective_to','>=',$period->ends_on))->orderByDesc('effective_from')->first();
   if(!$profile)throw ValidationException::withMessages(['payroll'=>'Confirm SSS / GSIS coverage and deductions for '.$e->employee_no.' in Workforce before computing.']);
   if(ContributionProfile::where('employee_id',$e->id)->where('effective_from','>',$period->starts_on)->where('effective_from','<=',$period->ends_on)->exists())throw ValidationException::withMessages(['payroll'=>'Contribution coverage changes within this cut-off; confirm payroll treatment before proceeding.']);
  }
  $monthly=$mode==='automatic_official'?$this->monthly($monthlyBasis,$profile->pension_scheme,$profile->philhealth_enabled,$profile->pagibig_enabled):array_fill_keys(['sss','gsis','philhealth','pagibig'],0);
  $override=DeductionOverride::where('employee_id',$e->id)->where('effective_from','<=',$period->starts_on)->where(fn($q)=>$q->whereNull('effective_to')->orWhere('effective_to','>=',$period->ends_on))->orderByDesc('effective_from')->first();
  $items=[];$sources=[];$sum=0;
  foreach($monthly as$name=>$amount){
   $cutoff=substr($period->starts_on,8,2)==='01'?$this->rounded($amount,2):intdiv($amount,2);
   $has=$override&&$override->$name!==null;if($has)$cutoff=Money::cents($override->$name);
   $items[$name]=Money::decimal($cutoff);$sum+=$cutoff;$sources[$name]=$has?'employee_override':($mode==='manual'?'manual_zero':'automatic');
  }
  if(Money::cents($items['sss'])&&Money::cents($items['gsis']))throw ValidationException::withMessages(['payroll'=>'SSS and GSIS pension deductions cannot both be applied to this employee.']);
  $taxable=max(0,$grossCents-$attendanceDeduction-$sum);
  $tax=$mode==='automatic_official'&&$profile->withholding_enabled?$this->withholding($taxable):0;
  foreach(['tax'=>$tax,'loan'=>0]as$name=>$amount){$has=$override&&$override->$name!==null;if($has)$amount=Money::cents($override->$name);$items[$name]=Money::decimal($amount);$sum+=$amount;$sources[$name]=$has?'employee_override':($mode==='manual'?'manual_zero':'automatic');}
  return $items+['total'=>Money::decimal($sum),'taxable_pay'=>Money::decimal($taxable),'mode'=>$mode,'monthly_basis'=>$monthlyBasis,'sources'=>$sources,'override_id'=>$override?->id,'override_reason'=>$override?->reason,'coverage'=>$profile?->only(['id','pension_scheme','philhealth_enabled','pagibig_enabled','withholding_enabled','effective_from','effective_to','reason']),'rate_profile'=>$mode==='automatic_official'?config('statutory.ph_2025'):null];
 }
}
