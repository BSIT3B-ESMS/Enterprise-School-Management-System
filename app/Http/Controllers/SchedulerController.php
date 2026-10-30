<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class SchedulerController extends Controller {
 public function index(){
  $offerings=DB::table('offerings as o')->join('subjects as s','s.id','=','o.subject_id')->join('sections as b','b.id','=','o.section_id')->where('o.active',true)->select('o.id','o.period_id','s.code','s.name','b.name as section','b.program_id','b.year_level','b.id as section_id')->get();
  $rooms=DB::table('rooms')->where('active',true)->get();$professors=DB::table('professors')->where('active',true)->get();$periods=DB::table('periods')->where('active',true)->get();
  $schedules=DB::table('schedules as c')->join('offerings as o','o.id','=','c.offering_id')->join('subjects as s','s.id','=','o.subject_id')->join('professors as p','p.id','=','c.professor_id')->join('rooms as r','r.id','=','c.room_id')->join('sections as b','b.id','=','o.section_id')->where('c.active',true)->select('c.*','s.code','s.name','o.period_id','r.code as room','r.floor','b.program_id','b.year_level','b.id as section_id','p.name as professor')->get();
  $programs=DB::table('programs')->where('active',true)->get();$sections=DB::table('sections')->where('active',true)->get();
  return view('scheduler',compact('offerings','rooms','professors','periods','schedules','programs','sections'));
 }
}