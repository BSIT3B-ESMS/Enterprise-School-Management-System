<?php
use App\Models\{ContributionProfile,DeductionOverride,AttendanceRecord,Payslip,WorkflowRequest};
use App\Services\{StatutoryService,PayrollService,Policy,ScheduleService};
use Illuminate\Support\Str;
function coverageFixture($employee,string $scheme='sss'):void {ContributionProfile::create(['employee_id'=>$employee->id,'effective_from'=>'2026-01-01','pension_scheme'=>$scheme,'philhealth_enabled'=>true,'pagibig_enabled'=>true,'withholding_enabled'=>true,'reason'=>'Confirmed test employment coverage','created_by'=>userFixture()->id]);}
function officialPolicyFixture():void {policyFixture();Policy::save(Policy::get()+[]);$p=Policy::get();$p['deduction_mode']='automatic_official';Policy::save($p);}
it('computes official employee shares and semi-monthly BIR tax in integer centavos',function(){
 $svc=app(StatutoryService::class);expect($svc->monthly('22000.00'))->toBe(['sss'=>110000,'gsis'=>0,'philhealth'=>55000,'pagibig'=>20000]);expect($svc->monthly('35000.00')['sss'])->toBe(175000);expect($svc->monthly('5249.99')['sss'])->toBe(25000);expect($svc->monthly('5250.00')['sss'])->toBe(27500);expect($svc->monthly('100000.00')['philhealth'])->toBe(250000);expect($svc->monthly('1500.00')['pagibig'])->toBe(1500);
 expect($svc->withholding(1041700))->toBe(0);expect($svc->withholding(1666700))->toBe(93750);expect($svc->withholding(3333300))->toBe(427070);expect($svc->withholding(8333300))->toBe(1677070);expect($svc->withholding(33333300))->toBe(9177070);
});
it('requires actual coverage and applies SSS or GSIS without double pension deductions',function(){
 $employee=employeeFixture();officialPolicyFixture();completedAttendance($employee);$pay=app(PayrollService::class);$period=$pay->period('2026-10-01');expect(fn()=>$pay->calculate($employee,$period))->toThrow(\Illuminate\Validation\ValidationException::class);
 coverageFixture($employee);$calc=$pay->calculate($employee,$period);expect($calc['net_pay'])->toBe('10075.00');expect($calc['sss'])->toBe('550.00');expect($calc['gsis'])->toBe('0.00');expect($calc['tax'])->toBe('0.00');
 ContributionProfile::where('employee_id',$employee->id)->update(['pension_scheme'=>'gsis']);$calc=$pay->calculate($employee,$period);expect($calc['net_pay'])->toBe('9635.00');expect($calc['gsis'])->toBe('990.00');expect($calc['sss'])->toBe('0.00');
});
it('uses BIR semi-monthly taxable compensation rather than halving a monthly tax estimate',function(){
 $employee=employeeFixture();$employee->salaryHistory()->update(['monthly_rate'=>'35000.00']);officialPolicyFixture();coverageFixture($employee);completedAttendance($employee);$pay=app(PayrollService::class);$calc=$pay->calculate($employee,$pay->period('2026-10-01'));
 expect($calc['tax'])->toBe('850.58');expect($calc['net_pay'])->toBe('15236.92');expect($calc['statutory']['taxable_pay'])->toBe('16087.50');
});
it('supports zero overrides recurring loans and frozen itemized releases',function(){
 $employee=employeeFixture();officialPolicyFixture();coverageFixture($employee);completedAttendance($employee);$admin=userFixture();
 $this->actingAs($admin)->withSession(['mfa_verified'=>true])->post('/employees/'.$employee->id.'/deductions',['effective_from'=>'2026-10-01','sss'=>'0.00','loan'=>'500.00','reason'=>'Authorized individual deduction override'])->assertSessionHasNoErrors();
 $pay=app(PayrollService::class);$period=$pay->period('2026-10-01');$pay->compute($period,$admin->id);$slip=Payslip::first();expect($slip->calculation['sss'])->toBe('0.00');expect($slip->calculation['loan'])->toBe('500.00');expect($slip->net_pay)->toBe('10093.80');
 $pay->release($slip,true,$admin->id);$this->post('/employees/'.$employee->id.'/deductions',['effective_from'=>'2026-10-01','tax'=>'100.00','reason'=>'Changed after release'])->assertSessionHasErrors('deductions');
 $this->get('/payslips/'.$slip->payslip_id)->assertOk()->assertSee('PhilHealth')->assertSee('Pag-IBIG')->assertSee('500.00');
});
it('preserves recorded kiosk punches when approving a missing-punch request',function(){
 $employee=employeeFixture();$employeeUser=userFixture($employee);$admin=userFixture();$in=\Illuminate\Support\Carbon::parse('2026-10-02 08:00','Asia/Manila')->utc();$record=AttendanceRecord::create(['employee_id'=>$employee->id,'work_date'=>'2026-10-02','am_in'=>$in,'source'=>'RFID/NFC']);
 $this->actingAs($employeeUser)->post('/requests',['type'=>'manual_attendance','work_date'=>'2026-10-02','am_out'=>'12:00','reason'=>'Missed lunch tap after reader failure'])->assertSessionHasNoErrors();$q=WorkflowRequest::first();
 $this->actingAs($admin)->withSession(['mfa_verified'=>true])->post('/requests/'.$q->id.'/decision',['decision'=>'approved','decision_note'=>'Proof confirms missing lunch OUT'])->assertSessionHasNoErrors();expect($record->fresh()->am_in->equalTo($in))->toBeTrue();expect($record->fresh()->am_out->timezone('Asia/Manila')->format('H:i'))->toBe('12:00');
 $this->actingAs($employeeUser)->post('/requests',['type'=>'manual_attendance','work_date'=>'2026-10-02','am_in'=>'09:00','reason'=>'Attempted overwrite of valid punch'])->assertSessionHasErrors('am_in');
});
it('rejects future and out-of-order manual corrections on the server',function(){
 $employee=employeeFixture();$user=userFixture($employee);$this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-02 10:00','Asia/Manila'));
 $this->actingAs($user)->post('/requests',['type'=>'manual_attendance','work_date'=>'2026-10-02','am_in'=>'11:00','reason'=>'Future time is not valid'])->assertSessionHasErrors('am_in');
 $this->post('/requests',['type'=>'manual_attendance','work_date'=>'2026-10-01','am_in'=>'08:00','am_out'=>'12:00','pm_in'=>'11:00','pm_out'=>'17:00','reason'=>'Unordered periods are invalid'])->assertSessionHasErrors('request');$this->travelBack();
});
it('credits only configured grace and reports actual working hours separately',function(){
 $employee=employeeFixture();$schedule=$employee->schedules()->first();$schedule->update(['grace_minutes'=>15]);$record=AttendanceRecord::create(['employee_id'=>$employee->id,'work_date'=>'2026-10-02','am_in'=>\Illuminate\Support\Carbon::parse('2026-10-02 08:30','Asia/Manila')->utc(),'am_out'=>\Illuminate\Support\Carbon::parse('2026-10-02 12:00','Asia/Manila')->utc(),'pm_in'=>\Illuminate\Support\Carbon::parse('2026-10-02 13:00','Asia/Manila')->utc(),'pm_out'=>\Illuminate\Support\Carbon::parse('2026-10-02 17:00','Asia/Manila')->utc()]);
 $m=app(ScheduleService::class)->minutes($schedule,'2026-10-02',$record);expect($m['late_minutes'])->toBe(15);expect($m['worked_minutes'])->toBe(450);expect($m['credited_minutes'])->toBe(465);expect($m['uncovered_minutes'])->toBe(15);
});
it('bulk releases one cut-off atomically and rolls back on any invalid payslip',function(){
 $e=employeeFixture();$other=employeeFixture('26-00002');policyFixture();completedAttendance($e);completedAttendance($other);$pay=app(PayrollService::class);$period=$pay->period('2026-10-01');$pay->compute($period,null);
 WorkflowRequest::create(['request_id'=>(string)Str::uuid(),'employee_id'=>$other->id,'type'=>'overtime','work_date'=>'2026-10-02','reason'=>'Not approved yet','payload'=>['minutes'=>60],'status'=>'pending']);
 expect(fn()=>$pay->releaseAll($period,null))->toThrow(\Illuminate\Validation\ValidationException::class);expect(Payslip::where('status','released')->count())->toBe(0);
 WorkflowRequest::query()->update(['status'=>'rejected']);expect($pay->releaseAll($period,null))->toBe(2);expect(Payslip::where('status','released')->count())->toBe(2);
});
it('filters workforce cards and attendance status without exposing other employees',function(){
 $e=employeeFixture();$other=employeeFixture('26-00002');$other->update(['status'=>'retired','rfid_uid'=>null]);policyFixture();completedAttendance($e);$admin=userFixture();
 $this->actingAs($admin)->withSession(['mfa_verified'=>true])->get('/?page=workforce&work_status=retired&card_status=unassigned')->assertOk()->assertSee('Employee 26-00002')->assertDontSee('Employee 26-00001');
 $this->get('/?page=attendance&from=2026-10-01&to=2026-10-15&record_status=completed')->assertOk()->assertSee('Completed');
 $this->actingAs(userFixture($e))->get('/?page=attendance&employee_id='.$other->id.'&from=2026-10-01&to=2026-10-15')->assertOk()->assertDontSee('Employee 26-00002');
});
it('explains an unreadable MFA secret and supports explicit local recovery',function(){
 $user=userFixture();\Illuminate\Support\Facades\DB::table('users')->where('id',$user->id)->update(['totp_secret'=>'unreadable-old-key-ciphertext']);
 $this->actingAs($user->fresh())->get('/mfa')->assertStatus(503)->assertSee('Authenticator key needs recovery')->assertDontSee('The MAC is invalid');
 $this->artisan('pms:reset-mfa',['login_id'=>$user->login_id,'--confirm'=>true])->assertSuccessful();expect($user->fresh()->totp_secret)->toBeNull();
});
it('records actual EMS employment changes and replays them without duplicating history',function(){
 $employee=employeeFixture();$row=['employee_no'=>$employee->employee_no,'full_name'=>$employee->full_name,'department'=>'New department','position'=>$employee->position,'monthly_rate'=>'22000.00','salary_effective_on'=>'2026-01-01','status'=>'active','updated_at'=>'2026-10-31T00:00:00Z'];$ems=app(\App\Services\EmsService::class);$ems->importEmployees([$row]);expect(\App\Models\EmployeeHistory::count())->toBe(1);$ems->importEmployees([$row]);expect(\App\Models\EmployeeHistory::count())->toBe(1);
 $this->actingAs(userFixture($employee))->get('/?page=profile')->assertOk()->assertSee('New department')->assertSee('Employment history from EMS');
});
it('exports the same employee and request filters as the approvals view',function(){
 $employee=employeeFixture();$other=employeeFixture('26-00002');$admin=userFixture();policyFixture();foreach([$employee,$other] as$e)WorkflowRequest::create(['request_id'=>(string)Str::uuid(),'employee_id'=>$e->id,'type'=>'overtime','work_date'=>'2026-10-02','reason'=>'Approved work','payload'=>['minutes'=>60],'status'=>'approved']);
 $response=$this->actingAs($admin)->withSession(['mfa_verified'=>true])->post('/reports',['type'=>'requests','from'=>'2026-10-01','to'=>'2026-10-15','q'=>$employee->employee_no,'status'=>'approved','request_type'=>'overtime'])->assertRedirect();
 $this->get($response->headers->get('Location'))->assertOk()->assertSee($employee->employee_no)->assertDontSee($other->employee_no)->assertSee('Prepared by / Payroll Officer')->assertSee('Reviewed by / HR Manager');
});
it('shows all released employee cut-offs and prevents another employee from viewing them',function(){
 $employee=employeeFixture();$other=employeeFixture('26-00002');policyFixture();completedAttendance($employee);completedAttendance($other);$pay=app(PayrollService::class);$first=$pay->period('2026-10-01');$pay->compute($first,null);$pay->releaseAll($first,null);
 completedAttendance($employee,'2026-10-16','2026-10-31');completedAttendance($other,'2026-10-16','2026-10-31');$second=$pay->period('2026-10-16');$pay->compute($second,null);$pay->releaseAll($second,null);
 $this->actingAs(userFixture($employee))->get('/?page=payslips')->assertOk()->assertSee('2026-10-01')->assertSee('2026-10-16')->assertDontSee('Employee 26-00002');
});
