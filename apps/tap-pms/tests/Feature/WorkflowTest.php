<?php
use App\Models\{WorkflowRequest,AttendanceRecord,PayrollAdjustment};
use Illuminate\Support\Str;
it('files own requests and requires administrator approval before changing attendance',function(){
 $e=employeeFixture();$u=userFixture($e);$a=userFixture();$response=$this->actingAs($u)->post('/requests',['type'=>'manual_attendance','work_date'=>'2026-10-02','reason'=>'Reader failed during my morning session','am_in'=>'08:00','am_out'=>'12:00','pm_in'=>'13:00','pm_out'=>'17:00','employee_id'=>999,'status'=>'approved'])->assertRedirect();
 $q=WorkflowRequest::first();expect($q->employee_id)->toBe($e->id);expect($q->status)->toBe('pending');$this->assertDatabaseCount('attendance_records',0);
 $this->post('/requests/'.$q->id.'/decision',['decision'=>'approved','decision_note'=>'Approved proof'])->assertForbidden();
 $this->actingAs($a)->withSession(['mfa_verified'=>true])->post('/requests/'.$q->id.'/decision',['decision'=>'approved','decision_note'=>'Approved proof'])->assertRedirect();
 $rec=AttendanceRecord::first();expect($rec->source)->toBe('Approved correction');expect($rec->am_in->timezone('Asia/Manila')->format('H:i'))->toBe('08:00');
 $this->post('/requests/'.$q->id.'/decision',['decision'=>'approved','decision_note'=>'Duplicate'])->assertSessionHasErrors('request');
});
it('keeps leave approval in EMS and posts approved loan deductions once',function(){
 $e=employeeFixture();$u=userFixture($e);$a=userFixture();
 $this->actingAs($u)->post('/requests',['type'=>'leave','work_date'=>'2026-11-02','ends_on'=>'2026-11-03','leave_type'=>'Sick leave','reason'=>'Medical appointment'])->assertRedirect();expect(WorkflowRequest::first()->status)->toBe('pending_ems');
 $this->actingAs($a)->withSession(['mfa_verified'=>true])->post('/requests/'.WorkflowRequest::first()->id.'/decision',['decision'=>'approved','decision_note'=>'Cannot approve leave here'])->assertSessionHasErrors('request');
 $this->actingAs($u)->post('/requests',['type'=>'loan','work_date'=>'2026-11-02','amount'=>'500.00','reason'=>'Payroll loan deduction'])->assertRedirect();$q=WorkflowRequest::where('type','loan')->first();
 $this->actingAs($a)->withSession(['mfa_verified'=>true])->post('/requests/'.$q->id.'/decision',['decision'=>'approved','decision_note'=>'Validated agreement'])->assertRedirect();$this->assertDatabaseCount('payroll_adjustments',1);expect(PayrollAdjustment::first()->amount)->toBe('500.00');
});
