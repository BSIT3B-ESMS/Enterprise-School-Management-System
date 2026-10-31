<?php
pest()->extend(Tests\TestCase::class)->use(Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Feature');
function employeeFixture(string $number='26-00001'): \App\Models\Employee {
 $e=\App\Models\Employee::create(['employee_no'=>$number,'full_name'=>'Employee '.$number,'department'=>'Operations','position'=>'Staff','monthly_rate'=>'22000.00','status'=>'active','rfid_uid'=>'CARD-'.$number]);
 \App\Models\WorkSchedule::create(['employee_id'=>$e->id,'effective_from'=>'2026-01-01','work_start'=>'08:00','lunch_start'=>'12:00','lunch_end'=>'13:00','work_end'=>'17:00','grace_minutes'=>0,'work_days'=>[1,2,3,4,5]]);
 \App\Models\SalaryHistory::create(['employee_id'=>$e->id,'effective_on'=>'2026-01-01','monthly_rate'=>'22000.00','source_reference'=>'TEST']);
 return $e;
}
function userFixture(?\App\Models\Employee $e=null): \App\Models\User {return \App\Models\User::create(['name'=>'Test user','login_id'=>$e ? $e->employee_no : 'ADMIN-'.strtoupper(\Illuminate\Support\Str::random(10)),'email'=>null,'password'=>'CorrectPassword123','role'=>$e?'employee':'admin','employee_id'=>$e?->id]);}
function policyFixture():void {\App\Services\Policy::save(['deduction_mode'=>'manual','monthly_divisor'=>22,'overtime_multiplier'=>'1.25','holidays'=>[],'signatory'=>'Payroll Officer','signatory_title'=>'Payroll Office']);}
function completedAttendance(\App\Models\Employee $e,string $start='2026-10-01',string $end='2026-10-15'):void {
 $day=\Illuminate\Support\Carbon::parse($start);while($day->toDateString()<=$end){if($day->isWeekday()){$date=$day->toDateString();$v=['employee_id'=>$e->id,'work_date'=>$date,'status'=>'Recorded','source'=>'TEST'];foreach(['am_in'=>'08:00','am_out'=>'12:00','pm_in'=>'13:00','pm_out'=>'17:00']as $field=>$time)$v[$field]=\Illuminate\Support\Carbon::parse($date.' '.$time,'Asia/Manila')->utc();\App\Models\AttendanceRecord::create($v);}$day->addDay();}
}
function callerHeaders(array $permissions):array {$plain=bin2hex(random_bytes(32));\App\Models\IntegrationToken::create(['caller'=>'test-'.\Illuminate\Support\Str::uuid(),'token_hash'=>hash('sha256',$plain),'permissions'=>$permissions]);return ['Authorization'=>'Bearer '.$plain,'Accept'=>'application/json','Idempotency-Key'=>(string)\Illuminate\Support\Str::uuid()];}
