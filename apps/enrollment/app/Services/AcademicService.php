<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class AcademicService {
 public static function fail(string $message): never {throw ValidationException::withMessages(['academic'=>$message]);}
 public function eligible(int $studentId,int $subjectId,?int $periodId=null): void {
  $student=DB::table('students')->find($studentId);
  if(!$student || !$student->active) self::fail('Student is inactive or missing.');
  $requirement=DB::table('curriculum_subjects')->where(['curriculum_id'=>$student->curriculum_id,'subject_id'=>$subjectId,'active'=>true])->first();if(!$requirement)self::fail('Subject is outside the assigned curriculum.');
  if($student->classification==='regular' && ($requirement->year_level!==$student->year_level || ($periodId && (int)$requirement->semester!==(int)DB::table('periods')->where('id',$periodId)->value('semester'))))self::fail('Regular students follow the prescribed year level and semester. Special academic arrangements require evaluation.');
  $records=DB::table('academic_records')->where('student_id',$studentId)->where('active',true)->whereIn('status',['PASSED','CREDITED'])->get();$completed=$records->pluck('subject_id')->all();
  if($student->year_level<$requirement->minimum_year)self::fail('This subject requires a higher recorded year standing.');
  if($requirement->minimum_passed_units){$units=DB::table('curriculum_subjects as c')->join('subjects as s','s.id','=','c.subject_id')->where(['c.curriculum_id'=>$student->curriculum_id,'c.active'=>true])->whereIn('s.id',$completed)->sum('s.units');if($units<$requirement->minimum_passed_units)self::fail('This subject requires more completed curriculum units for year standing.');}
  if(in_array($subjectId,$completed)) self::fail('This subject has already been completed or credited.');
  if($periodId && DB::table('academic_overrides')->where(['student_id'=>$studentId,'subject_id'=>$subjectId,'period_id'=>$periodId,'active'=>true])->exists())return;
    $satisfies=function($requirement)use($records){
   $attempts=$records->where('subject_id',$requirement->required_subject_id);
   if($requirement->minimum_grade===null)return $attempts->isNotEmpty();
   $order=app(SettingsService::class)->get('grade_order',config('enrollment.grade_order'));
   if(!in_array($order,['lower','higher']))self::fail('Minimum-grade validation requires the institution’s grade-order policy to be configured.');
   return $attempts->contains(function($record)use($requirement,$order){if($record->status==='CREDITED')return true;if(!is_numeric($record->grade))return false;return $order==='lower'?(float)$record->grade<=(float)$requirement->minimum_grade:(float)$record->grade>=(float)$requirement->minimum_grade;});
  };
  $groups=DB::table('prerequisites')->where(['subject_id'=>$subjectId,'active'=>true])->get()->groupBy('alternative_group');
  foreach($groups as $group=>$requirements){
   if((int)$group===0) {foreach($requirements as $r) if(!$satisfies($r)) self::fail('A mandatory prerequisite has not been completed.');}
   elseif(!$requirements->contains(fn($r)=>$satisfies($r))) self::fail('An alternative prerequisite group has not been satisfied.');
  }
 }
 public function prerequisite(array $data,?int $id=null): void {
  if((int)$data['subject_id']===(int)$data['required_subject_id']) self::fail('A subject cannot require itself.');
  if(DB::table('curriculum_subjects as target')->join('curriculum_subjects as required',fn($join)=>$join->on('required.curriculum_id','=','target.curriculum_id')->where('required.subject_id',$data['required_subject_id'])->where('required.active',true))->where(['target.subject_id'=>$data['subject_id'],'target.active'=>true])->where(fn($q)=>$q->whereColumn('required.year_level','>','target.year_level')->orWhere(fn($q)=>$q->whereColumn('required.year_level','target.year_level')->whereColumn('required.semester','>=','target.semester')))->exists())self::fail('A prerequisite must be assigned to an earlier year or semester in every shared curriculum.');
  $edges=DB::table('prerequisites')->where('active',true)->when($id,fn($q)=>$q->where('id','!=',$id))->get()->groupBy('subject_id');
  $pending=[(int)$data['required_subject_id']];$visited=[];
  while($pending){$node=array_pop($pending);if($node===(int)$data['subject_id']) self::fail('Circular prerequisite relationships are not allowed.');if(isset($visited[$node]))continue;$visited[$node]=true;foreach($edges[$node]??[] as $edge)$pending[]=(int)$edge->required_subject_id;}
 }
 public function schedule(array $data,?int $id=null): void {
  $offering=DB::table('offerings')->find($data['offering_id']);
  if(!$offering || !$offering->active) self::fail('Choose an active class offering.');if(!DB::table('periods')->where(['id'=>$offering->period_id,'active'=>true])->exists())self::fail('The academic period is inactive.');
  app(TermWorkflowService::class)->mutable($offering->period_id);
  $subject=DB::table('subjects')->find($offering->subject_id);$room=DB::table('rooms')->find($data['room_id']);$professor=DB::table('professors')->find($data['professor_id']);$section=DB::table('sections')->find($offering->section_id);
  if(!$room->active || !$professor->active || !$section->active || !$subject->active) self::fail('Room, professor, section and subject must be active.');
  if($offering->allowed_days && !in_array($data['day'],explode(',',$offering->allowed_days)))self::fail('The class offering is not permitted on this day.');
  if(!in_array($data['day'],explode(',',$subject->allowed_days)))self::fail('The subject is not permitted on this day.');
  if($room->dedicated_subject_id && $room->dedicated_subject_id!=$subject->id)self::fail('This room is dedicated to another subject.');
  if($room->type!==$subject->type) self::fail('The room type does not match the subject type.');
  if(DB::table('room_closures')->where(['room_id'=>$room->id,'period_id'=>$offering->period_id,'day'=>$data['day'],'active'=>true])->whereRaw('substr(start_time,1,5) < ?', [substr($data['end_time'],0,5)])->whereRaw('substr(end_time,1,5) > ?', [substr($data['start_time'],0,5)])->exists())self::fail('The room is unavailable due to a scheduled closure.');
  if($offering->capacity>$room->capacity || $offering->capacity>$section->capacity)self::fail('Class capacity exceeds room or section capacity.');
  if(!DB::table('professor_subjects')->where(['professor_id'=>$professor->id,'subject_id'=>$subject->id,'active'=>true])->exists())self::fail('The professor is not authorized to teach this subject.');
  if(!DB::table('professor_availabilities')->where(['professor_id'=>$professor->id,'day'=>$data['day'],'active'=>true])->whereRaw('substr(start_time,1,5) <= ?', [substr($data['start_time'],0,5)])->whereRaw('substr(end_time,1,5) >= ?', [substr($data['end_time'],0,5)])->exists())self::fail('The class is outside the professor’s recorded availability.');
  $conflicts=DB::table('schedules as s')->join('offerings as o','o.id','=','s.offering_id')->where('o.period_id',$offering->period_id)->where('s.active',true)->where('s.day',$data['day'])->whereRaw('substr(s.start_time,1,5) < ?', [substr($data['end_time'],0,5)])->whereRaw('substr(s.end_time,1,5) > ?', [substr($data['start_time'],0,5)])->when($id,fn($q)=>$q->where('s.id','!=',$id))->where(function($q)use($data,$offering){$q->where('s.room_id',$data['room_id'])->orWhere('s.professor_id',$data['professor_id'])->orWhere('o.section_id',$offering->section_id);});
  if($conflicts->exists())self::fail('This time overlaps another assignment for the room, professor or section.');
 }
 public function audit(string $action,string $entity,?int $id=null,array $details=[]): void {
  DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'action'=>$action,'entity'=>$entity,'entity_id'=>$id,'details'=>json_encode($details),'created_at'=>now(),'updated_at'=>now()]);
 }
}
