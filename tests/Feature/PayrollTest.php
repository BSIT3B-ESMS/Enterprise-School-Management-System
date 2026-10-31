<?php
use App\Models\{AttendanceRecord,WorkflowRequest,PayrollAdjustment,Payslip,SalaryHistory};
use App\Services\{PayrollService,Policy};
use Illuminate\Support\Str;
it('computes full attendance on the server without floating point',function(){
 $e=employeeFixture();policyFixture();completedAttendance($e);$svc=app(PayrollService::class);$p=$svc->period('2026-10-03');$c=$svc->calculate($e,$p);
 expect($p->starts_on)->toBe('2026-10-01');expect($p->ends_on)->toBe('2026-10-15');expect($c['net_pay'])->toBe('11000.00');expect($c['days'])->toHaveCount(11);
 $svc->compute($p,null);$this->assertDatabaseCount('payslips',1);expect(Payslip::first()->net_pay)->toBe('11000.00');
});
it('counts PM-only work and deducts the unworked morning only once',function(){
 $e=employeeFixture();policyFixture();completedAttendance($e);AttendanceRecord::where('employee_id',$e->id)->where('work_date','2026-10-02')->update(['am_in'=>null,'am_out'=>null]);
 $c=app(PayrollService::class)->calculate($e,app(PayrollService::class)->period('2026-10-01'));
 expect($c['attendance_deduction'])->toBe('500.00');expect($c['net_pay'])->toBe('10500.00');expect($c['incomplete_dates'])->not->toContain('2026-10-02');
});
it('pays approved overtime and applies approved employee deductions only',function(){
 $e=employeeFixture();policyFixture();completedAttendance($e);
 WorkflowRequest::create(['request_id'=>(string)Str::uuid(),'employee_id'=>$e->id,'type'=>'overtime','work_date'=>'2026-10-02','reason'=>'Approved overtime','payload'=>['minutes'=>60],'status'=>'approved']);
 WorkflowRequest::create(['request_id'=>(string)Str::uuid(),'employee_id'=>$e->id,'type'=>'overtime','work_date'=>'2026-10-02','reason'=>'Still pending','payload'=>['minutes'=>120],'status'=>'pending']);
 $a=userFixture();PayrollAdjustment::create(['adjustment_id'=>(string)Str::uuid(),'employee_id'=>$e->id,'applies_on'=>'2026-10-02','kind'=>'deduction','label'=>'Approved loan','amount'=>'1000.00','created_by'=>$a->id]);
 $c=app(PayrollService::class)->calculate($e,app(PayrollService::class)->period('2026-10-01'));expect($c['overtime_pay'])->toBe('156.25');expect($c['net_pay'])->toBe('10156.25');
});
it('protects release per employee and period and prevents stale or incomplete release',function(){
 $e=employeeFixture();$e2=employeeFixture('26-00002');policyFixture();completedAttendance($e);completedAttendance($e2);$admin=userFixture();$svc=app(PayrollService::class);$period=$svc->period('2026-10-01');$svc->compute($period,$admin->id);$slip=Payslip::where('employee_no',$e->employee_no)->first();
 $svc->release($slip,true,$admin->id);$u=userFixture($e);$this->actingAs($u)->get('/payslips/'.$slip->payslip_id)->assertOk()->assertSee('11000.00');
 $other=Payslip::where('employee_no',$e2->employee_no)->first();$this->get('/payslips/'.$other->payslip_id)->assertForbidden();
 $this->actingAs(userFixture($e2))->get('/payslips/'.$other->payslip_id)->assertForbidden();
 $svc->release($slip,false,$admin->id);$this->actingAs($u)->get('/payslips/'.$slip->payslip_id)->assertForbidden();
 AttendanceRecord::where('employee_id',$e->id)->where('work_date','2026-10-02')->update(['pm_out'=>null]);
 $this->actingAs($admin)->withSession(['mfa_verified'=>true])->post('/payroll/'.$slip->id.'/release',['action'=>'release'])->assertSessionHasErrors('payroll');
});
it('excludes inactive employees and weights effective salary changes',function(){
 $e=employeeFixture();$inactive=employeeFixture('26-00002');$inactive->update(['status'=>'inactive']);policyFixture();completedAttendance($e);
 SalaryHistory::create(['employee_id'=>$e->id,'effective_on'=>'2026-10-08','monthly_rate'=>'24000.00']);$svc=app(PayrollService::class);$p=$svc->period('2026-10-01');$svc->compute($p,null);
 $this->assertDatabaseCount('payslips',1);expect(Payslip::first()->net_pay)->toBe('11545.45');
});
it('requires an authorized payroll policy and a covering schedule',function(){
 $e=employeeFixture();$p=app(PayrollService::class)->period('2026-10-01');expect(fn()=>app(PayrollService::class)->calculate($e,$p))->toThrow(\Illuminate\Validation\ValidationException::class);
 policyFixture();$e->schedules()->delete();expect(fn()=>app(PayrollService::class)->calculate($e,$p))->toThrow(\Illuminate\Validation\ValidationException::class);
});
it('creates real PDF payslips and controlled reports with employee numbers and signatories',function(){
 $e=employeeFixture();policyFixture();completedAttendance($e);$svc=app(PayrollService::class);$p=$svc->period('2026-10-01');$svc->compute($p,null);$a=userFixture();$s=Payslip::first();
 $pdf=$this->actingAs($a)->withSession(['mfa_verified'=>true])->get('/payslips/'.$s->payslip_id.'?pdf=1')->assertOk()->assertHeader('Content-Type','application/pdf');expect(substr($pdf->getContent(),0,4))->toBe('%PDF');
 $response=$this->post('/reports',['type'=>'payroll','from'=>'2026-10-01','to'=>'2026-10-15'])->assertRedirect();
 $this->get($response->headers->get('Location'))->assertOk()->assertSee('26-00001')->assertSee('Payroll Officer')->assertSee('11000.00');
});
it('blocks new adjustments against a released employee cut-off',function(){
 $e=employeeFixture();policyFixture();completedAttendance($e);$admin=userFixture();$svc=app(PayrollService::class);$period=$svc->period('2026-10-01');$svc->compute($period,$admin->id);$svc->release(Payslip::first(),true,$admin->id);
 $this->actingAs($admin)->withSession(['mfa_verified'=>true])->post('/payroll/adjustments',['employee_id'=>$e->id,'applies_on'=>'2026-10-02','kind'=>'deduction','label'=>'New deduction','amount'=>'100.00'])->assertRedirect()->assertSessionHasErrors('adjustment');$this->assertDatabaseCount('payroll_adjustments',0);
});
