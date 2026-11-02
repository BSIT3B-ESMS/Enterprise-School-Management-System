<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class CurriculumController extends Controller {
 public function show(int $id){
  $curriculum=DB::table('curriculums as c')->join('programs as p','p.id','=','c.program_id')->where('c.id',$id)->select('c.*','p.name as program')->first();abort_unless($curriculum,404);
  $requirements=DB::table('curriculum_subjects as c')->join('subjects as s','s.id','=','c.subject_id')->where('curriculum_id',$id)->select('c.*','s.code','s.name','s.units')->orderBy('c.year_level')->orderBy('c.semester')->orderBy('s.code')->get();
  return view('curriculum',compact('curriculum','requirements'));
 }
}
