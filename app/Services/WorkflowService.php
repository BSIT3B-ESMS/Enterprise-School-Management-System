<?php
namespace App\Services;
use App\Models\{WorkflowRequest,Employee,AttendanceRecord,PayrollAdjustment,Payslip,PayrollPeriod};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class WorkflowService {
 public function decide(WorkflowRequest $request,string $decision,string $note,int $actor):void{
  DB::transaction(function()use($request,$decision,$note,$actor){$q=WorkflowRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
   if($q->status!=='pending')throw ValidationException::withMessages(['request'=>'This request has already been decided or is assigned to EMS.']);
   if($q->type==='leave')throw ValidationException::withMessages(['request'=>'EMS is the leave approver. Forward the request and synchronize the decision.']);
   $e=Employee::findOrFail($q->employee_id);if($decision==='approved'&&!$e->eligible())throw ValidationException::withMessages(['request'=>'Employee is inactive.']);
   $period=PayrollPeriod::where('starts_on','<=',$q->work_date)->where('ends_on','>=',$q->work_date)->first();
   if($period&&Payslip::where('period_id',$period->period_id)->where('employee_no',$e->employee_no)->where('status','released')->exists())throw ValidationException::withMessages(['request'=>'Withdraw this employee’s payslip before changing its source records.']);
   if($decision==='approved'){
    if($q->type==='manual_attendance'){
     app(AttendanceCorrectionService::class)->apply($q,$actor);
    } elseif($q->type==='schedule_change')app(ScheduleService::class)->save($e,$q->payload+['effective_from'=>$q->work_date]);
    elseif(in_array($q->type,['deduction','loan','tax'],true))PayrollAdjustment::create(['adjustment_id'=>(string)Str::uuid(),'employee_id'=>$e->id,'workflow_request_id'=>$q->id,'applies_on'=>$q->work_date,'kind'=>'deduction','label'=>ucfirst($q->type).' approved request','amount'=>$q->payload['amount'],'created_by'=>$actor]);
   }
   $q->update(['status'=>$decision,'reviewed_by'=>$actor,'reviewed_at'=>now(),'decision_note'=>$note]);Audit::record('request_'.$decision,'workflow_requests',$q->request_id,['type'=>$q->type]);
  });
 }
}
