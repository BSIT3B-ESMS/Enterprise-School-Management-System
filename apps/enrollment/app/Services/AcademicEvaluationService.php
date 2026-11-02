<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;

class AcademicEvaluationService {
 public function evaluate(int $id,array $data): void {
  DB::transaction(function()use($id,$data){
   // Same lock order as enrollment: periods, student, dependent records.
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   $case=DB::table('special_cases')->find($id); abort_unless($case,404);
   app(TermWorkflowService::class)->mutable($case->period_id);
   app(AccessService::class)->student($case->student_id);
   $student=DB::table('students')->where('id',$case->student_id)->lockForUpdate()->first();
   $case=DB::table('special_cases')->where('id',$id)->lockForUpdate()->first();
   if($case->status!=='pending')AcademicService::fail('This request has already been evaluated.');
   if(!$student->active || $case->curriculum_id!=$student->curriculum_id)AcademicService::fail('The student or curriculum has changed. Submit a new evaluation request.');
   if(DB::table('enrollments')->where(['student_id'=>$student->id,'period_id'=>$case->period_id])->exists())AcademicService::fail('Evaluate academic credits before proposing enrollment for this period.');
   foreach($data['credits']??[] as $credit){
    if(!DB::table('curriculum_subjects')->where(['curriculum_id'=>$student->curriculum_id,'subject_id'=>$credit['subject_id'],'active'=>true])->exists())AcademicService::fail('Every equivalent subject must belong to the assigned curriculum.');
    $recordId=null;
    if($credit['decision']==='approved'){
     if($data['status']!=='approved')AcademicService::fail('A rejected evaluation cannot award credits.');
     if(DB::table('academic_records')->where(['student_id'=>$student->id,'subject_id'=>$credit['subject_id'],'active'=>true])->whereIn('status',['PASSED','CREDITED'])->exists())AcademicService::fail('A proposed equivalent subject is already completed or credited.');
     if(DB::table('academic_records')->where(['student_id'=>$student->id,'subject_id'=>$credit['subject_id'],'period_id'=>$case->period_id])->exists())AcademicService::fail('An academic record already exists for this subject and period. Review that record before awarding credit.');
     $recordId=DB::table('academic_records')->insertGetId(['student_id'=>$student->id,'subject_id'=>$credit['subject_id'],'period_id'=>$case->period_id,'status'=>'CREDITED','remarks'=>'Department Head evaluation #'.$id.'. '.$credit['reason'],'created_at'=>now(),'updated_at'=>now()]);
    }
    DB::table('credit_evaluations')->insert($credit+['special_case_id'=>$id,'academic_record_id'=>$recordId,'created_at'=>now(),'updated_at'=>now()]);
   }
   DB::table('special_cases')->where('id',$id)->update(['status'=>$data['status'],'evaluation'=>$data['evaluation'],'reviewer_id'=>auth()->id(),'updated_at'=>now()]);
   app(AcademicService::class)->audit('evaluated','special_cases',$id,$data);
   app(NotificationService::class)->studentEvent($student->id,'Academic evaluation '.$data['status'],'/special-cases/'.$id);
  },3);
 }
 public function assignCurriculum(int $studentId,array $data): void {
  DB::transaction(function()use($studentId,$data){
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   $student=DB::table('students')->where('id',$studentId)->lockForUpdate()->first(); abort_unless($student,404);
   if($student->curriculum_id!=(int)$data['previous_curriculum_id'])AcademicService::fail('The curriculum assignment has changed. Refresh this record.');
   $curriculum=DB::table('curriculums')->find($data['curriculum_id']);
   $old=DB::table('curriculums')->find($student->curriculum_id);
   if(!$curriculum->active || $curriculum->program_id!=$old->program_id || $curriculum->id==$old->id)AcademicService::fail('Select a different active curriculum in the student’s program.');
   if(!DB::table('curriculum_subjects')->where(['curriculum_id'=>$curriculum->id,'active'=>true])->exists())AcademicService::fail('Add curriculum requirements before assigning this version.');
   if(DB::table('enrollments')->where('student_id',$studentId)->whereNotIn('status',['finalized','term_closed'])->exists())AcademicService::fail('Resolve open enrollment proposals before changing the curriculum.');
   DB::table('curriculum_assignments')->insert(['student_id'=>$studentId,'previous_curriculum_id'=>$old->id,'curriculum_id'=>$curriculum->id,'user_id'=>auth()->id(),'reason'=>$data['reason'],'created_at'=>now(),'updated_at'=>now()]);
   DB::table('students')->where('id',$studentId)->update(['curriculum_id'=>$curriculum->id,'updated_at'=>now()]);
   app(AcademicService::class)->audit('curriculum_assigned','students',$studentId,$data);
   app(NotificationService::class)->studentEvent($studentId,'Curriculum assignment updated','/records/'.$studentId);
  },3);
 }
}
