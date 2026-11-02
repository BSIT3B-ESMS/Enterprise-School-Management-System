<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class AccessService {
 public function programs(): array {return DB::table('department_head_programs')->where(['user_id'=>auth()->id(),'active'=>true])->pluck('program_id')->all();}
 public function student(int $id): void {
  $student=DB::table('students')->find($id);abort_unless($student,404);
  abort_if(auth()->user()->role==='professor',403);
  if(auth()->user()->role==='student')abort_unless($student->user_id===auth()->id(),403);
  if(auth()->user()->role==='department_head'){$curriculum=DB::table('curriculums')->find($student->curriculum_id);abort_unless(in_array($curriculum->program_id,$this->programs()),403);}
 }
 public function studentIds(): array {return DB::table('students as s')->join('curriculums as c','c.id','=','s.curriculum_id')->whereIn('c.program_id',$this->programs())->pluck('s.id')->all();}
}
