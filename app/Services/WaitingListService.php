<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class WaitingListService {
 public function reason(object $student,int $subjectId,int $periodId):?string {
  $semester=DB::table('periods')->where('id',$periodId)->value('semester');$requirement=DB::table('curriculum_subjects')->where(['curriculum_id'=>$student->curriculum_id,'subject_id'=>$subjectId,'active'=>true])->first();
  if(!$requirement)AcademicService::fail('Requested subjects must belong to the assigned curriculum.');
  try{app(AcademicService::class)->eligible($student->id,$subjectId,$periodId);}catch(ValidationException $e){return $e->getMessage();}
  if((int)$requirement->semester!==(int)$semester)return 'Not offered in this semester of the curriculum.';
  if(!DB::table('offerings as o')->join('schedules as s','s.offering_id','=','o.id')->where(['o.period_id'=>$periodId,'o.subject_id'=>$subjectId,'o.active'=>true,'s.active'=>true])->exists())return 'Waiting for an active class offering and schedule.';
  return null;
 }
 public function request(object $student,int $periodId,array $ids,string $remarks):?int {
  return DB::transaction(function()use($student,$periodId,$ids,$remarks){
   app(TermWorkflowService::class)->mutable($periodId);if(DB::table('enrollments')->where(['student_id'=>$student->id,'period_id'=>$periodId,'status'=>'finalized'])->exists())AcademicService::fail('You already have a COR for this semester. Request subjects when the next semester opens.');
   $ready=[];foreach($ids as $id){if(DB::table('academic_records')->where(['student_id'=>$student->id,'subject_id'=>$id,'active'=>true])->whereIn('status',['PASSED','CREDITED'])->exists())AcademicService::fail('Completed or credited subjects cannot be requested again.');$reason=$this->reason($student,(int)$id,$periodId);if($reason){DB::table('subject_waiting_list')->updateOrInsert(['student_id'=>$student->id,'subject_id'=>$id,'status'=>'waiting'],['requested_period_id'=>$periodId,'reason'=>$reason,'updated_at'=>now(),'created_at'=>now()]);}else $ready[]=(int)$id;}
   $caseId=null;if($ready){$open=DB::table('special_cases')->where(['student_id'=>$student->id,'period_id'=>$periodId,'curriculum_id'=>$student->curriculum_id])->whereIn('status',['pending','approved'])->first();if($open)AcademicService::fail('An academic request is already being processed for this semester.');$caseId=DB::table('special_cases')->insertGetId(['student_id'=>$student->id,'period_id'=>$periodId,'curriculum_id'=>$student->curriculum_id,'requested_subject_ids'=>json_encode($ready),'request'=>$remarks,'status'=>'pending','created_at'=>now(),'updated_at'=>now()]);}
   app(AcademicService::class)->audit('back_subjects_requested','students',$student->id,['period_id'=>$periodId,'subject_ids'=>$ids,'case_id'=>$caseId]);app(NotificationService::class)->studentEvent($student->id,$caseId?'Back-subject request sent to Department Head':'Subjects placed on waiting list',$caseId?'/special-cases/'.$caseId:'/waiting-list');return $caseId;
  },3);
 }
 public function release(int $periodId):int {
  return DB::transaction(function()use($periodId){$count=0;
   foreach(DB::table('subject_waiting_list')->where('status','waiting')->get()->groupBy('student_id') as $studentId=>$entries){$student=DB::table('students')->find($studentId);if(!$student||!$student->active)continue;if(DB::table('enrollments')->where(['student_id'=>$studentId,'period_id'=>$periodId])->exists())continue;
    $eligible=$entries->filter(function($entry)use($student,$periodId){try{return $this->reason($student,$entry->subject_id,$periodId)===null;}catch(ValidationException){return false;}});if($eligible->isEmpty())continue;
    $open=DB::table('special_cases')->where(['student_id'=>$studentId,'period_id'=>$periodId,'curriculum_id'=>$student->curriculum_id])->whereIn('status',['pending','approved'])->first();if($open && $open->status!=='pending')continue;
    $ids=array_values(array_unique(array_merge($open?json_decode($open->requested_subject_ids??'[]',true):[],$eligible->pluck('subject_id')->all())));
    if($open){$id=$open->id;DB::table('special_cases')->where('id',$id)->update(['requested_subject_ids'=>json_encode($ids),'updated_at'=>now()]);}else $id=DB::table('special_cases')->insertGetId(['student_id'=>$studentId,'period_id'=>$periodId,'curriculum_id'=>$student->curriculum_id,'requested_subject_ids'=>json_encode($ids),'request'=>'Waiting-list subjects are now offered and prerequisites are satisfied.','status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
    DB::table('subject_waiting_list')->whereIn('id',$eligible->pluck('id'))->update(['status'=>'released','released_case_id'=>$id,'updated_at'=>now()]);$count+=$eligible->count();app(NotificationService::class)->studentEvent($studentId,'Waiting subjects released for Department Head evaluation','/special-cases/'.$id);
   }return $count;
  },3);
 }
}
