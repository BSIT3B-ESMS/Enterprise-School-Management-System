<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class TermWorkflowService {
 public function current():?object{return DB::table('periods')->where(['is_current'=>true,'active'=>true])->whereNull('closed_at')->first();}
 public function mutable(int $periodId,?int $year=null):object {
  $period=DB::table('periods')->find($periodId);if(!$period||!$period->active||$period->closed_at)AcademicService::fail('This semester is closed or inactive and is view only.');
  if($this->current() && !$period->is_current)AcademicService::fail('Only the current semester can be edited.');
  if($year && DB::table('schedule_locks')->where(['period_id'=>$periodId,'year_level'=>$year])->exists())AcademicService::fail('The schedule for this semester and year level is finalized and view only.');
  return $period;
 }
 public function activate(int $id):void {
  DB::transaction(function()use($id){
   $period=DB::table('periods')->find($id);if(!$period||!$period->active||$period->closed_at)AcademicService::fail('Select an open semester.');
   if(!$period->start_date||!$period->end_date)AcademicService::fail('Set the semester start and end dates before opening it.');
   DB::table('periods')->where('is_current',true)->update(['is_current'=>false]);DB::table('periods')->where('id',$id)->update(['is_current'=>true,'updated_at'=>now()]);
   $this->assignAll($id);app(WaitingListService::class)->release($id);app(AcademicService::class)->audit('semester_activated','periods',$id);
  },3);
 }
 public function dates(int $id,array $dates):void {
  DB::transaction(function()use($id,$dates){
   $period=$this->mutable($id);if(DB::table('schedule_locks')->where('period_id',$id)->exists())AcademicService::fail('Dates are fixed after a year-level schedule is finalized.');
   foreach($dates as $field=>$value)if($value!==$period->$field && $value<now()->toDateString())AcademicService::fail('New semester dates cannot be in the past.');
   if($dates['end_date']<=$dates['start_date'])AcademicService::fail('The end date must be after the start date.');
   if(DB::table('periods')->where('academic_year',$period->academic_year)->where('id','!=',$id)->whereNotNull('start_date')->where('start_date','<=',$dates['end_date'])->where('end_date','>=',$dates['start_date'])->exists())AcademicService::fail('Semester date ranges cannot overlap in the same academic year.');
   DB::table('periods')->where('id',$id)->update($dates+['updated_at'=>now()]);app(AcademicService::class)->audit('semester_dates_updated','periods',$id,$dates);
  },3);
 }
 public function assignAll(int $periodId):array {
  $results=[];foreach(DB::table('students')->where('active',true)->orderBy('admission_id')->orderBy('id')->pluck('id') as $id)$results[$id]=$this->assign((int)$id,$periodId);return $results;
 }
 public function assign(int $studentId,int $periodId,?int $sectionId=null):int {
  return DB::transaction(function()use($studentId,$periodId,$sectionId){
   $this->mutable($periodId);$student=DB::table('students')->find($studentId);if(!$student||!$student->active)AcademicService::fail('Select an active student.');
   $program=DB::table('curriculums')->where('id',$student->curriculum_id)->value('program_id');
   $existing=DB::table('student_sections')->where(['student_id'=>$studentId,'period_id'=>$periodId])->first();if($existing && $sectionId===null){$assigned=DB::table('sections')->find($existing->section_id);if($assigned->active && $assigned->year_level===$student->year_level && $assigned->program_id===$program)return $existing->section_id;}
   if(DB::table('enrollments')->where(['student_id'=>$studentId,'period_id'=>$periodId])->exists())AcademicService::fail('Resolve the enrollment proposal before changing the primary section.');
   $sections=DB::table('sections')->where(['program_id'=>$program,'period_id'=>$periodId,'year_level'=>$student->year_level,'active'=>true])->orderBy('name')->get();
   $section=null;foreach($sections as $candidate){if($sectionId && $candidate->id!=$sectionId)continue;$used=DB::table('student_sections')->where('section_id',$candidate->id)->where('student_id','!=',$studentId)->count();if($used<$candidate->capacity){$section=$candidate;break;}}
   if(!$section && $sectionId)AcademicService::fail('Choose a section in the correct program/year with a free membership place.');
   if(!$section){$code=DB::table('programs')->where('id',$program)->value('code');$i=0;do{$suffix='';$n=$i++;do{$suffix=chr(65+$n%26).$suffix;$n=intdiv($n,26)-1;}while($n>=0);$name=$code.' '.$student->year_level.$suffix;}while(DB::table('sections')->where(['period_id'=>$periodId,'name'=>$name])->exists());
    $sectionId=DB::table('sections')->insertGetId(['program_id'=>$program,'period_id'=>$periodId,'year_level'=>$student->year_level,'name'=>$name,'capacity'=>(int)app(SettingsService::class)->get('default_section_capacity','40'),'active'=>true,'created_at'=>now(),'updated_at'=>now()]);$section=DB::table('sections')->find($sectionId);app(AcademicService::class)->audit('section_auto_opened','sections',$sectionId);
   }
   DB::table('student_sections')->updateOrInsert(['student_id'=>$studentId,'period_id'=>$periodId],['section_id'=>$section->id,'created_at'=>$existing?->created_at??now(),'updated_at'=>now()]);app(AcademicService::class)->audit('primary_section_assigned','students',$studentId,['period_id'=>$periodId,'section_id'=>$section->id]);return $section->id;
  },3);
 }
 public function lock(int $periodId,int $year):void {
  DB::transaction(function()use($periodId,$year){$period=$this->mutable($periodId,$year);if(!$period->start_date||!$period->end_date)AcademicService::fail('Set semester dates first.');
   foreach(DB::table('sections')->where(['period_id'=>$periodId,'year_level'=>$year,'active'=>true])->get() as $section){$required=DB::table('curriculum_subjects as r')->join('curriculums as c','c.id','=','r.curriculum_id')->where(['c.program_id'=>$section->program_id,'c.active'=>true,'r.active'=>true,'r.year_level'=>$year,'r.semester'=>$period->semester,'r.elective'=>false])->pluck('r.subject_id')->unique();$offered=DB::table('offerings')->where(['section_id'=>$section->id,'period_id'=>$periodId,'active'=>true])->pluck('subject_id');if($required->diff($offered)->isNotEmpty())AcademicService::fail('Schedule all prescribed required subjects for '.$section->name.' before finalizing the year level.');}
   $offerings=DB::table('offerings as o')->join('sections as s','s.id','=','o.section_id')->where(['o.period_id'=>$periodId,'s.year_level'=>$year,'o.active'=>true])->select('o.*')->get();if($offerings->isEmpty())AcademicService::fail('Schedule at least one class for this year level.');
   foreach($offerings as $offering){$subject=DB::table('subjects')->find($offering->subject_id);$meetings=DB::table('schedules')->where(['offering_id'=>$offering->id,'active'=>true])->get();$minutes=$meetings->sum(fn($m)=>(strtotime($m->end_time)-strtotime($m->start_time))/60);if(!$subject->weekly_hours||abs($minutes-(float)$subject->weekly_hours*60)>.1)AcademicService::fail('Verify weekly hours and fully schedule '.$subject->code.' before finalizing.');foreach($meetings as $meeting)app(AcademicService::class)->schedule((array)$meeting,$meeting->id);}
   DB::table('schedule_locks')->insert(['period_id'=>$periodId,'year_level'=>$year,'user_id'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);app(AcademicService::class)->audit('year_schedule_finalized','periods',$periodId,['year_level'=>$year]);
  },3);
 }
 public function close(int $id):int {
  return DB::transaction(function()use($id){$period=$this->mutable($id);if(!$period->is_current)AcademicService::fail('Close only the current semester.');
   DB::table('periods')->where('id',$id)->update(['closed_at'=>now(),'is_current'=>false,'updated_at'=>now()]);
   // Preserve offers, schedules and immutable CORs. Unaccepted proposals expire with a recorded event.
   foreach(DB::table('enrollments')->where('period_id',$id)->where('status','!=','finalized')->get() as $enrollment){DB::table('enrollments')->where('id',$enrollment->id)->update(['status'=>'term_closed','updated_at'=>now()]);app(AcademicService::class)->audit('proposal_closed_with_term','enrollments',$enrollment->id);}
   [$start,$end]=array_map('intval',explode('-',$period->academic_year));$semester=(int)$period->semester===1?2:1;if($semester===1){$start++;$end++;}$year=$start.'-'.$end;
   $next=DB::table('periods')->where(['academic_year'=>$year,'semester'=>$semester])->first();$nextId=$next?->id??DB::table('periods')->insertGetId(['name'=>$year.' Semester '.$semester,'academic_year'=>$year,'semester'=>$semester,'active'=>true,'is_current'=>false,'created_at'=>now(),'updated_at'=>now()]);
   app(AcademicService::class)->audit('semester_closed','periods',$id,['next_period_id'=>$nextId]);return $nextId;
  },3);
 }
 public function promote(int $studentId,int $periodId):void {
  DB::transaction(function()use($studentId,$periodId){$student=DB::table('students')->find($studentId);$period=DB::table('periods')->find($periodId);if(!$student||$student->year_level>=4)AcademicService::fail('Only students below year four can be promoted.');if(!$period||!$period->closed_at||(int)$period->semester!==2)AcademicService::fail('Finish the second semester before promotion.');
   if(DB::table('student_promotions')->where(['student_id'=>$studentId,'academic_year'=>$period->academic_year])->exists())AcademicService::fail('This student was already promoted for that academic year.');
   $current=$this->current();if($current && $current->academic_year>$period->academic_year && DB::table('enrollments')->where(['student_id'=>$studentId,'period_id'=>$current->id])->exists())AcademicService::fail('Resolve the next-year enrollment before changing year standing.');$requirements=DB::table('curriculum_subjects')->where(['curriculum_id'=>$student->curriculum_id,'year_level'=>$student->year_level,'active'=>true])->pluck('subject_id');$recorded=DB::table('academic_records')->where(['student_id'=>$studentId,'active'=>true])->whereIn('status',['PASSED','FAILED','INCOMPLETE','CREDITED'])->pluck('subject_id');if($requirements->isEmpty() || $requirements->diff($recorded)->isNotEmpty())AcademicService::fail('Professors must submit grades for every required subject before promotion.');
   if(DB::table('enrollments')->where('student_id',$studentId)->whereNotIn('status',['finalized','term_closed'])->exists())AcademicService::fail('Resolve open proposals before promotion.');DB::table('student_promotions')->insert(['student_id'=>$studentId,'period_id'=>$periodId,'academic_year'=>$period->academic_year,'from_year'=>$student->year_level,'created_at'=>now(),'updated_at'=>now()]);DB::table('students')->where('id',$studentId)->update(['year_level'=>$student->year_level+1,'updated_at'=>now()]);app(AcademicService::class)->audit('student_promoted','students',$studentId,['period_id'=>$periodId,'from_year'=>$student->year_level,'to_year'=>$student->year_level+1]);if($current && $current->academic_year>$period->academic_year)$this->assign($studentId,$current->id);
  },3);
 }
}
