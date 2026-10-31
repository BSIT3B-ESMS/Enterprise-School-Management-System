<?php
use App\Models\{WorkflowRequest,AttendanceRecord,LeaveBalance,Payslip};
use App\Services\{EmsService,PayrollService};
use Illuminate\Support\Facades\{Http,DB};
it('connects PMS filing to EMS approval and read-only credits before payroll release',function(){
 $employee=employeeFixture();policyFixture();completedAttendance($employee);$user=userFixture($employee);
 $this->actingAs($user)->post('/requests',['type'=>'leave','work_date'=>'2026-10-02','ends_on'=>'2026-10-02','leave_type'=>'sick','reason'=>'Medical appointment'])->assertSessionHasNoErrors();
 $q=WorkflowRequest::firstOrFail();expect($q->status)->toBe('pending_ems');
 $leave=['leave_id'=>'EMS-L-ROUNDTRIP','employee_no'=>$employee->employee_no,'starts_on'=>'2026-10-02','ends_on'=>'2026-10-02','is_paid'=>true,'status'=>'approved','request_id'=>$q->request_id];
 $balance=['employee_no'=>$employee->employee_no,'leave_type'=>'sick','remaining_days'=>'7.50','as_of'=>'2026-10-31','updated_at'=>'2026-10-31T00:00:00Z'];
 config(['ems.enabled'=>true,'ems.base_url'=>'https://ems.example.test','ems.token'=>'test-roundtrip-token','ems.leave_requests_path'=>'/api/v1/leave-requests','ems.leave_request_fields'=>['request_id'=>'request_id','employee_no'=>'employee_no','starts_on'=>'starts_on','ends_on'=>'ends_on','leave_type'=>'leave_type','reason'=>'reason'],'ems.leaves_path'=>'/api/v1/leave-decisions','ems.leave_fields'=>array_combine(array_keys($leave),array_keys($leave)),'ems.balances_path'=>'/api/v1/leave-balances','ems.balance_fields'=>array_combine(array_keys($balance),array_keys($balance))]);
 Http::fake(function($request)use($q,$employee,$leave,$balance){
  if($request->method()==='POST')return Http::response(['data'=>['request_id'=>$q->request_id,'employee_no'=>$employee->employee_no],'meta'=>(object)[]],201);
  return Http::response(['data'=>[str_contains($request->url(),'leave-balances')?$balance:$leave],'meta'=>['has_more'=>false,'next_cursor'=>null]]);
 });
 $ems=app(EmsService::class);$ems->sendLeave($q);expect($q->fresh()->status)->toBe('pending_ems');
 Http::assertSent(fn($r)=>$r->method()==='POST'&&$r->hasHeader('Idempotency-Key',$q->request_id)&&$r['starts_on']==='2026-10-02');
 $ems->sync('leaves');$ems->sync('balances');expect($q->fresh()->status)->toBe('approved');expect(LeaveBalance::first()->remaining_days)->toBe('7.50');
 AttendanceRecord::where('employee_id',$employee->id)->where('work_date','2026-10-02')->delete();
 DB::table('ems_sync_runs')->insert(['resource'=>'employees','status'=>'success','record_count'=>1,'created_at'=>now(),'updated_at'=>now()]);
 $pay=app(PayrollService::class);$period=$pay->period('2026-10-01');$pay->compute($period,null);expect(Payslip::first()->net_pay)->toBe('11000.00');$pay->release(Payslip::first(),true,null);expect(Payslip::first()->status)->toBe('released');expect(LeaveBalance::first()->remaining_days)->toBe('7.50');
});
