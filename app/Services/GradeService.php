<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class GradeService {
 public function submit(int $offeringId,array $grades):int {
  return DB::transaction(function()use($offeringId,$grades){
   $offering=DB::table('offerings')->find($offeringId);abort_unless($offering,404);$professor=DB::table('professors')->where(['user_id'=>auth()->id(),'active'=>true])->first();
   abort_unless($professor && DB::table('schedules')->where(['offering_id'=>$offeringId,'professor_id'=>$professor->id,'active'=>true])->exists(),403);
   $period=DB::table('periods')->find($offering->period_id);if($period->closed_at)AcademicService::fail('This semester is closed. Grade changes require the authorized academic-record process.');
   $enrolled=DB::table('enrollments as e')->join('proposal_items as i',fn($j)=>$j->on('i.enrollment_id','=','e.id')->on('i.version','=','e.version'))->where(['i.offering_id'=>$offeringId,'e.status'=>'finalized'])->pluck('e.student_id')->all();$count=0;
   foreach($grades as $studentId=>$grade){if($grade===null||$grade==='')continue;abort_unless(in_array((int)$studentId,array_map('intval',$enrolled),true),403);
    if(!in_array($grade,['1.00','1.25','1.50','1.75','2.00','2.25','2.50','2.75','3.00','5.00','INC'],true))AcademicService::fail('Choose a listed transmuted grade or INC.');
    $status=$grade==='INC'?'INCOMPLETE':((float)$grade<=3?'PASSED':'FAILED');$existing=DB::table('academic_records')->where(['student_id'=>$studentId,'subject_id'=>$offering->subject_id,'period_id'=>$offering->period_id])->first();
    if($existing && ($existing->status==='CREDITED'||($existing->submitted_by && $existing->submitted_by!==auth()->id())))AcademicService::fail('An existing academic decision is owned by another authorized reviewer.');
    if($existing && $existing->grade===$grade && $existing->status===$status)continue;
    if(DB::table('enrollments')->where('student_id',$studentId)->where('period_id','!=',$offering->period_id)->whereNotIn('status',['finalized','term_closed'])->exists())AcademicService::fail('Resolve the student’s open proposals before changing prerequisite results.');
    DB::table('academic_records')->updateOrInsert(['student_id'=>$studentId,'subject_id'=>$offering->subject_id,'period_id'=>$offering->period_id],['grade'=>$grade,'status'=>$status,'active'=>true,'submitted_by'=>auth()->id(),'submitted_at'=>now(),'remarks'=>'Professor submission for offering #'.$offeringId,'created_at'=>$existing?->created_at??now(),'updated_at'=>now()]);
    if(in_array($status,['FAILED','INCOMPLETE']))DB::table('students')->where(['id'=>$studentId,'classification'=>'regular'])->update(['classification'=>'irregular','updated_at'=>now()]);
    app(AcademicService::class)->audit('professor_grade_submitted','students',(int)$studentId,['offering_id'=>$offeringId,'before'=>$existing?['grade'=>$existing->grade,'status'=>$existing->status]:null,'grade'=>$grade,'status'=>$status]);app(NotificationService::class)->studentEvent((int)$studentId,'Grade posted: '.$grade,'/grades');$count++;
   }
   $current=app(TermWorkflowService::class)->current();if($current)app(WaitingListService::class)->release($current->id);return $count;
  },3);
 }
}
