<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class EnrollmentService {
 public function __construct(private AcademicService $academic){}
 public function items(object $enrollment) {
  $curriculumId=DB::table('students')->where('id',$enrollment->student_id)->value('curriculum_id');
  return DB::table('proposal_items as i')->join('offerings as o','o.id','=','i.offering_id')->join('subjects as s','s.id','=','o.subject_id')->join('sections as b','b.id','=','o.section_id')->leftJoin('curriculum_subjects as r',fn($q)=>$q->on('r.subject_id','=','s.id')->where('r.curriculum_id',$curriculumId))->where('i.enrollment_id',$enrollment->id)->where('i.version',$enrollment->version)->select('i.*','s.code',DB::raw('COALESCE(r.source_title,s.name) as name'),'s.units','b.name as section','o.capacity','o.enrolled_count')->get();
 }
 public function validateSelection(object $e,array $ids): void {
  if(!$ids)AcademicService::fail('Select at least one class offering.');
  if(!DB::table('periods')->where(['id'=>$e->period_id,'active'=>true])->exists())AcademicService::fail('The academic period is inactive.');
  $meetings=collect();
  foreach($ids as $id){
   $o=DB::table('offerings')->find($id);
   if(!$o || !$o->active || $o->period_id!=$e->period_id) AcademicService::fail('All offerings must be active in the enrollment period.');
   $this->academic->eligible($e->student_id,$o->subject_id,$e->period_id);
   if($o->enrolled_count >= $o->capacity)AcademicService::fail('A selected class is full.');
   $classes=DB::table('schedules')->where(['offering_id'=>$id,'active'=>true])->get();
   if($classes->isEmpty())AcademicService::fail('Every selected offering needs a complete class schedule.');
   foreach($classes as $class){$this->academic->schedule((array)$class,$class->id);foreach($meetings as $other)if($class->day===$other->day && substr($class->start_time,0,5)<substr($other->end_time,0,5) && substr($class->end_time,0,5)>substr($other->start_time,0,5))AcademicService::fail('The student has overlapping class schedules.');$meetings->push($class);}
  }
  $sections=DB::table('offerings')->whereIn('id',$ids)->pluck('section_id')->unique();
  foreach($sections as $sectionId){$section=DB::table('sections')->find($sectionId);$count=DB::table('enrollments as e')->join('proposal_items as i','i.enrollment_id','=','e.id')->join('offerings as o','o.id','=','i.offering_id')->whereColumn('i.version','e.version')->where('e.status','finalized')->where('o.section_id',$sectionId)->where('e.student_id','!=',$e->student_id)->distinct()->count('e.student_id');if($count >= $section->capacity)AcademicService::fail('The section has reached its student capacity. The Registrar must choose or approve another section.');}
  $subjects=DB::table('offerings')->whereIn('id',$ids)->pluck('subject_id');
  if($subjects->unique()->count()!==count($ids))AcademicService::fail('A subject cannot be selected twice.');
  $student=DB::table('students')->find($e->student_id);
  if($student->classification==='regular'){
   $completed=DB::table('academic_records')->where(['student_id'=>$student->id,'active'=>true])->whereIn('status',['PASSED','CREDITED'])->pluck('subject_id');
   $required=DB::table('curriculum_subjects as c')->join('subjects as s','s.id','=','c.subject_id')->where(['c.curriculum_id'=>$student->curriculum_id,'c.year_level'=>$student->year_level,'c.semester'=>DB::table('periods')->where('id',$e->period_id)->value('semester'),'c.active'=>true,'c.elective'=>false])->whereNotIn('s.id',$completed)->whereNotIn('s.id',$subjects)->pluck('s.code');
   if($required->isNotEmpty())AcademicService::fail('The regular proposal is missing prescribed required subjects: '.$required->implode(', ').'. A special academic arrangement requires evaluation.');
  }
 }
 public function propose(int $studentId,int $periodId,array $ids,bool $special=false,bool $forward=false): int {
  return DB::transaction(function()use($studentId,$periodId,$ids,$special,$forward){
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   app(AccessService::class)->student($studentId);
   DB::table('students')->where('id',$studentId)->lockForUpdate()->first();
   $student=DB::table('students')->find($studentId);
   app(TermWorkflowService::class)->mutable($periodId);
   if($special && $student->classification==='regular')AcademicService::fail('Department Head proposals are for evaluated special classifications. Regular proposals are prepared by the Registrar.');
   if($student->classification!=='regular' && !DB::table('special_cases')->where(['student_id'=>$studentId,'period_id'=>$periodId,'status'=>'approved','curriculum_id'=>$student->curriculum_id])->exists())AcademicService::fail('A Department Head must approve the academic evaluation first.');
   if($special){$builder=app(SpecialScheduleService::class)->choices($studentId,$periodId);if(array_diff($builder['base']->pluck('id')->all(),$ids))AcademicService::fail('Retain the eligible primary-section assignments.');if(array_diff($ids,$builder['base']->merge($builder['extras'])->pluck('id')->all()))AcademicService::fail('Only requested, eligible additional classes can be assigned.');}
   $e=DB::table('enrollments')->where(['student_id'=>$studentId,'period_id'=>$periodId])->lockForUpdate()->first();
   if($e && $e->status==='finalized')AcademicService::fail('Finalized enrollment requires an authorized revision process.');
   if(!$special && $student->classification!=='regular'){if(!$e)AcademicService::fail('The student must first accept the special academic proposal.');$accepted=$this->acceptedSpecialItems($e);if(array_diff($accepted,$ids))AcademicService::fail('The final proposal must retain the accepted special class assignments. Ask the Department Head to revise the special proposal first.');}
   $e=$e??(object)['student_id'=>$studentId,'period_id'=>$periodId,'version'=>0];
   $this->validateSelection($e,$ids);
   $version=$e->version+1;
   $values=['student_id'=>$studentId,'period_id'=>$periodId,'version'=>$version,'accepted_version'=>null,'status'=>$special?($forward?'special_ready':'special_proposed'):'proposed','updated_at'=>now()];
   if(isset($e->id)){DB::table('enrollments')->where('id',$e->id)->update($values);$id=$e->id;}else{$id=DB::table('enrollments')->insertGetId($values+['created_at'=>now()]);}
   foreach($ids as $offering)DB::table('proposal_items')->insert(['enrollment_id'=>$id,'version'=>$version,'offering_id'=>$offering,'created_at'=>now(),'updated_at'=>now()]);
   $this->event($id,$version,$special?($forward?'special_proposal_staged':'special_proposal_sent'):'proposal_sent');if($forward)DB::table('special_cases')->where(['student_id'=>$studentId,'period_id'=>$periodId,'status'=>'approved','curriculum_id'=>$student->curriculum_id])->update(['forwarded_at'=>now(),'updated_at'=>now()]);return $id;
  });
 }
 public function decide(int $id,int $version,bool $accept,?string $remarks): void {
  DB::transaction(function()use($id,$version,$accept,$remarks){
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   $e=DB::table('enrollments')->where('id',$id)->lockForUpdate()->first();
   app(TermWorkflowService::class)->mutable($e->period_id);
   if($e->version!==$version || !in_array($e->status,['proposed','special_proposed']))AcademicService::fail('This proposal has changed or already has a decision. Refresh and review the latest version.');
   if(!$accept && !trim($remarks??''))AcademicService::fail('Please explain the revision you need.');
   $special=$e->status==='special_proposed';
   $status=$accept?'accepted':'revision_requested';
   DB::table('enrollments')->where('id',$id)->update(['status'=>$status,'accepted_version'=>$accept?$version:null,'updated_at'=>now()]);
   if($accept && $special)$this->event($id,$version,'special_accepted',$remarks);
   $this->event($id,$version,$status,$remarks);
   if($accept)$this->finalize($id);
  });
 }
 public function finalize(int $id): void {
  DB::transaction(function()use($id){
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   $e=DB::table('enrollments')->where('id',$id)->lockForUpdate()->first();
   if($e->status==='finalized')return;
   app(TermWorkflowService::class)->mutable($e->period_id);
   $student=DB::table('students')->find($e->student_id);if($student->classification!=='regular' && (!DB::table('special_cases')->where(['student_id'=>$student->id,'period_id'=>$e->period_id,'status'=>'approved','curriculum_id'=>$student->curriculum_id])->exists()))AcademicService::fail('Special academic evaluation and student acceptance are required.');
   if($student->classification!=='regular')$this->acceptedSpecialItems($e);
   if($e->status!=='accepted' || $e->accepted_version!==$e->version)AcademicService::fail('The student must accept the current final proposal before finalization.');
   $ids=$this->items($e)->pluck('offering_id')->sort()->values()->all();
   DB::table('offerings')->whereIn('id',$ids)->orderBy('id')->lockForUpdate()->get();
   $this->validateSelection($e,$ids);
   foreach($ids as $offering){$updated=DB::table('offerings')->where('id',$offering)->whereColumn('enrolled_count','<','capacity')->increment('enrolled_count');if(!$updated)AcademicService::fail('A selected class has filled. Revise this proposal.');}
   DB::table('enrollments')->where('id',$id)->update(['status'=>'finalized','finalized_at'=>now(),'updated_at'=>now()]);
   $student=DB::table('students')->join('users','users.id','=','students.user_id')->join('curriculums','curriculums.id','=','students.curriculum_id')->join('programs','programs.id','=','curriculums.program_id')->where('students.id',$e->student_id)->select('students.*','users.name','programs.name as program','programs.code as program_code')->first();
   $items=$this->items($e)->map(function($i){$i->meetings=DB::table('schedules as c')->join('rooms as r','r.id','=','c.room_id')->join('professors as p','p.id','=','c.professor_id')->where(['c.offering_id'=>$i->offering_id,'c.active'=>true])->select('c.day','c.start_time','c.end_time','r.code as room','p.name as professor')->get();return $i;});
   $period=DB::table('periods')->find($e->period_id);
   $snapshot=app(SettingsService::class)->corIdentity()+['student'=>$student,'period'=>$period->name,'academic_year'=>$period->academic_year,'semester'=>$period->semester,'start_date'=>$period->start_date,'end_date'=>$period->end_date,'items'=>$items,'finalized_at'=>now()->toIso8601String()];
   DB::table('cor_versions')->insert(['enrollment_id'=>$id,'version'=>$e->version,'snapshot'=>json_encode($snapshot),'created_at'=>now(),'updated_at'=>now()]);
   $this->event($id,$e->version,'finalized');
  },3);
 }
 public function revise(int $id,string $remarks):void {
  DB::transaction(function()use($id,$remarks){DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   $e=DB::table('enrollments')->where('id',$id)->lockForUpdate()->first();app(TermWorkflowService::class)->mutable($e->period_id);if($e->status!=='finalized')AcademicService::fail('Only finalized enrollment can be reopened through this process.');$ids=$this->items($e)->pluck('offering_id')->sort()->all();DB::table('offerings')->whereIn('id',$ids)->orderBy('id')->lockForUpdate()->get();foreach($ids as $offering)DB::table('offerings')->where('id',$offering)->where('enrolled_count','>',0)->decrement('enrolled_count');DB::table('enrollments')->where('id',$id)->update(['status'=>'revision_requested','accepted_version'=>null,'finalized_at'=>null,'updated_at'=>now()]);$this->event($id,$e->version,'authorized_revision',$remarks);},3);
 }
 public function acceptedSpecialItems(object $e): array {
  $latest=DB::table('enrollment_events')->where('enrollment_id',$e->id)->whereIn('action',['special_proposal_sent','special_proposal_staged'])->orderByDesc('version')->first();
  if(!$latest || ($latest->action!=='special_proposal_staged' && !DB::table('enrollment_events')->where(['enrollment_id'=>$e->id,'version'=>$latest->version,'action'=>'special_accepted'])->exists()))AcademicService::fail('A Department Head must send or forward the latest special academic proposal.');
  return DB::table('proposal_items')->where(['enrollment_id'=>$e->id,'version'=>$latest->version])->pluck('offering_id')->all();
 }
 private function event(int $id,int $version,string $action,?string $remarks=null): void {DB::table('enrollment_events')->insert(['enrollment_id'=>$id,'version'=>$version,'user_id'=>auth()->id(),'action'=>$action,'remarks'=>$remarks,'created_at'=>now(),'updated_at'=>now()]);$this->academic->audit($action,'enrollments',$id);app(NotificationService::class)->studentEvent(DB::table('enrollments')->where('id',$id)->value('student_id'),ucwords(str_replace('_',' ',$action)),'/enrollments/'.$id);}
}
