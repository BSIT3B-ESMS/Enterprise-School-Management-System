<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class SpecialScheduleService {
 public function choices(int $studentId,int $periodId):array {
  $student=DB::table('students')->find($studentId);$case=DB::table('special_cases')->where(['student_id'=>$studentId,'period_id'=>$periodId,'curriculum_id'=>$student->curriculum_id,'status'=>'approved'])->orderByDesc('id')->first();
  $requested=json_decode($case->requested_subject_ids??'[]',true);$membership=DB::table('student_sections')->where(['student_id'=>$studentId,'period_id'=>$periodId])->first();
  $classes=DB::table('offerings as o')->join('subjects as s','s.id','=','o.subject_id')->join('sections as b','b.id','=','o.section_id')->where(['o.period_id'=>$periodId,'o.active'=>true])->select('o.*','s.code','s.name','s.units','b.name as section')->orderBy('s.code')->get();$base=collect();$extras=collect();
  foreach($classes as $class){try{app(AcademicService::class)->eligible($studentId,$class->subject_id,$periodId);}catch(\Illuminate\Validation\ValidationException){continue;}$class->meetings=DB::table('schedules as c')->join('rooms as r','r.id','=','c.room_id')->where(['c.offering_id'=>$class->id,'c.active'=>true])->select('c.*','r.code as room')->get();if($class->meetings->isEmpty()||$class->enrolled_count>=$class->capacity)continue;if($membership && $class->section_id===$membership->section_id)$base->push($class);elseif(!$requested || in_array($class->subject_id,$requested))$extras->push($class);}
  return compact('base','extras');
 }
}
