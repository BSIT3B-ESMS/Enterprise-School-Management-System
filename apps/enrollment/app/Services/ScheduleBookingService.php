<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class ScheduleBookingService {
 public function book(array $data,bool $preview=false):?int {
  $level=DB::transactionLevel();DB::beginTransaction();
  try{
   $section=DB::table('sections')->find($data['section_id']);if(!$section||!$section->active)AcademicService::fail('Choose an active section.');$period=app(TermWorkflowService::class)->mutable($section->period_id,$section->year_level);if(!$period->start_date||!$period->end_date)AcademicService::fail('Set semester dates before booking rooms.');
   if(!DB::table('curriculum_subjects as r')->join('curriculums as c','c.id','=','r.curriculum_id')->where(['c.program_id'=>$section->program_id,'r.subject_id'=>$data['subject_id'],'r.year_level'=>$section->year_level,'r.semester'=>$period->semester,'r.active'=>true,'c.active'=>true])->exists())AcademicService::fail('Choose a prescribed subject for this program, year and semester.');
   $subject=DB::table('subjects')->find($data['subject_id']);if(!$subject->active||!$subject->weekly_hours)AcademicService::fail('Verify the subject’s weekly contact hours before scheduling.');
   $offering=DB::table('offerings')->where(['section_id'=>$section->id,'subject_id'=>$subject->id,'period_id'=>$period->id,'active'=>true])->first();
   if($offering && DB::table('proposal_items')->where('offering_id',$offering->id)->exists())AcademicService::fail('This class has proposal history and cannot be rescheduled.');
   $id=$offering?->id??DB::table('offerings')->insertGetId(['subject_id'=>$subject->id,'section_id'=>$section->id,'period_id'=>$period->id,'capacity'=>$section->capacity,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
   $existing=DB::table('schedules')->where(['offering_id'=>$id,'active'=>true])->get()->sum(fn($m)=>(strtotime($m->end_time)-strtotime($m->start_time))/60);$minutes=(strtotime($data['end_time'])-strtotime($data['start_time']))/60;if($minutes<=0||$existing+$minutes>(float)$subject->weekly_hours*60)AcademicService::fail('This meeting exceeds the subject’s remaining weekly contact hours.');
   $meeting=array_intersect_key($data,array_flip(['room_id','professor_id','day','start_time','end_time']))+['offering_id'=>$id];app(AcademicService::class)->schedule($meeting);
   if($preview){DB::rollBack();return null;}
   $schedule=DB::table('schedules')->insertGetId($meeting+['created_at'=>now(),'updated_at'=>now()]);app(AcademicService::class)->audit('room_grid_booking','schedules',$schedule,$meeting);if($period->is_current)app(WaitingListService::class)->release($period->id);DB::commit();return $schedule;
  }finally{while(DB::transactionLevel()>$level)DB::rollBack();}
 }
}
