<?php
namespace Tests\Support;
use App\Models\User;
use Illuminate\Support\Facades\DB;
trait AcademicFixture {
 protected function row(string $table,array $data):int{if($table==='special_cases' && !isset($data['curriculum_id']))$data['curriculum_id']=DB::table('students')->where('id',$data['student_id'])->value('curriculum_id');return DB::table($table)->insertGetId($data+['created_at'=>now(),'updated_at'=>now()]);}
 protected function fixture():array {
  $registrar=User::factory()->create(['role'=>'registrar','must_change_password'=>false]);$user=User::factory()->create(['role'=>'student','must_change_password'=>false]);
  $program=$this->row('programs',['code'=>'BSIT','name'=>'Information Technology']);$period=$this->row('periods',['name'=>'2026-2027 Semester 1']);$curriculum=$this->row('curriculums',['program_id'=>$program,'name'=>'2026']);
  $subject=$this->row('subjects',['code'=>'IT001','name'=>'Introduction to IT','units'=>3,'type'=>'lecture','allowed_days'=>'Mon,Tue']);
  $this->row('curriculum_subjects',['curriculum_id'=>$curriculum,'subject_id'=>$subject,'year_level'=>1,'semester'=>1]);
  $student=$this->row('students',['user_id'=>$user->id,'admission_id'=>'ADM-1','curriculum_id'=>$curriculum]);
  $section=$this->row('sections',['program_id'=>$program,'period_id'=>$period,'name'=>'BSIT 1A','year_level'=>1,'capacity'=>2]);
  $building=$this->row('buildings',['name'=>'Main']);$floor=$this->row('floors',['building_id'=>$building,'name'=>'3']);
  $room=$this->row('rooms',['code'=>'301','building'=>'Main','floor'=>'3','building_id'=>$building,'floor_id'=>$floor,'type'=>'lecture','capacity'=>2]);
  $professor=$this->row('professors',['employee_id'=>'EMP-1','name'=>'Professor Example','department'=>'CCS','employment_type'=>'FULL_TIME']);
  $this->row('professor_subjects',['professor_id'=>$professor,'subject_id'=>$subject]);
  $this->row('professor_availabilities',['professor_id'=>$professor,'day'=>'Mon','start_time'=>'07:00','end_time'=>'17:00']);
  $offering=$this->row('offerings',['subject_id'=>$subject,'section_id'=>$section,'period_id'=>$period,'capacity'=>2]);
  $schedule=$this->row('schedules',['offering_id'=>$offering,'professor_id'=>$professor,'room_id'=>$room,'day'=>'Mon','start_time'=>'08:00','end_time'=>'09:00']);
  return compact('registrar','user','program','period','curriculum','subject','student','section','room','building','floor','professor','offering','schedule');
 }

}
