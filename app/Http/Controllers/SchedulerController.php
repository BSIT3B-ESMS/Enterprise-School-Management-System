<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class SchedulerController extends Controller {
 public function rooms(\Illuminate\Http\Request $request){
  $request->validate(['section_id'=>'nullable|integer|exists:sections,id','day'=>'nullable|in:Mon,Tue,Wed,Thu,Fri,Sat,Sun','college_id'=>'nullable|integer|exists:colleges,id','year_level'=>'nullable|integer|min:1|max:4']);
  $current=app(\App\Services\TermWorkflowService::class)->current();$colleges=DB::table('colleges')->where('active',true)->get();$sections=DB::table('sections as s')->join('programs as p','p.id','=','s.program_id')->where('s.active',true)->when($current,fn($q)=>$q->where('s.period_id',$current->id))->when($request->filled('college_id'),fn($q)=>$q->where('p.college_id',$request->integer('college_id')))->when($request->filled('year_level'),fn($q)=>$q->where('s.year_level',$request->integer('year_level')))->select('s.*')->orderBy('s.name')->get();$section=$sections->firstWhere('id',$request->integer('section_id'))??$sections->first();$period=$section?DB::table('periods')->find($section->period_id):$current;$day=$request->input('day','Mon');
  $subjects=$section?DB::table('subjects as s')->where('s.active',true)->whereIn('s.id',DB::table('curriculum_subjects as r')->join('curriculums as c','c.id','=','r.curriculum_id')->where(['c.program_id'=>$section->program_id,'c.active'=>true,'r.active'=>true,'r.year_level'=>$section->year_level,'r.semester'=>$period->semester])->select('r.subject_id'))->orderBy('s.code')->get():collect();
  foreach($subjects as $subject)$subject->scheduled_minutes=DB::table('schedules as m')->join('offerings as o','o.id','=','m.offering_id')->where(['o.subject_id'=>$subject->id,'o.section_id'=>$section->id,'o.active'=>true,'m.active'=>true])->get()->sum(fn($m)=>(strtotime($m->end_time)-strtotime($m->start_time))/60);
  $rooms=DB::table('rooms')->where('active',true)->orderBy('code')->get();$professors=DB::table('professors')->where('active',true)->orderBy('name')->get();$qualifications=DB::table('professor_subjects')->where('active',true)->get();$availability=DB::table('professor_availabilities')->where('active',true)->get();$meetings=DB::table('schedules as m')->join('offerings as o','o.id','=','m.offering_id')->join('subjects as s','s.id','=','o.subject_id')->join('sections as b','b.id','=','o.section_id')->where(['m.active'=>true,'o.period_id'=>$period?->id??0,'m.day'=>$day])->select('m.*','s.code','b.name as section')->get();return view('room-scheduler',compact('current','period','colleges','sections','section','day','subjects','rooms','professors','qualifications','availability','meetings'));
 }
 public function book(\Illuminate\Http\Request $request,\App\Services\ScheduleBookingService $service){$data=$request->validate(['section_id'=>'required|integer|exists:sections,id','subject_id'=>'required|integer|exists:subjects,id','room_id'=>'required|integer|exists:rooms,id','professor_id'=>'required|integer|exists:professors,id','day'=>'required|in:Mon,Tue,Wed,Thu,Fri,Sat,Sun','start_time'=>'required|date_format:H:i','end_time'=>'required|date_format:H:i|after:start_time']);$preview=$request->boolean('preview');$service->book($data,$preview);return $request->expectsJson()?response()->json(['message'=>$preview?'Booking checks passed. Confirm to save.':'Meeting saved.']):back()->with('status','Meeting saved.');}
 public function preview(\Illuminate\Http\Request $request,\App\Services\AcademicService $service){
  $data=$request->validate(config('academic.schedules')+['id'=>'nullable|integer|exists:schedules,id']);
  return DB::transaction(function()use($data,$service){
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   $id=$data['id']??null;
   $ids=[$data['offering_id']];if($id)$ids[]=DB::table('schedules')->where('id',$id)->value('offering_id');
   if(DB::table('proposal_items')->whereIn('offering_id',$ids)->exists())\App\Services\AcademicService::fail('This class has proposal history. Create a new offering and revised proposals instead.');
   $service->schedule($data,$id);
   return response()->json(['message'=>'Room, professor, section, days and capacity checks passed. Confirm to save; all checks run again when saving.']);
  });
 }
 public function index(){
  $offerings=DB::table('offerings as o')->join('subjects as s','s.id','=','o.subject_id')->join('sections as b','b.id','=','o.section_id')->where('o.active',true)->select('o.id','o.subject_id','o.period_id','s.code','s.name','b.name as section','b.program_id','b.year_level','b.id as section_id')->get();
  $rooms=DB::table('rooms')->where('active',true)->get();$professors=DB::table('professors')->where('active',true)->get();$periods=DB::table('periods')->where('active',true)->get();
  $schedules=DB::table('schedules as c')->join('offerings as o','o.id','=','c.offering_id')->join('subjects as s','s.id','=','o.subject_id')->join('professors as p','p.id','=','c.professor_id')->join('rooms as r','r.id','=','c.room_id')->join('sections as b','b.id','=','o.section_id')->where('c.active',true)->select('c.*','s.code','s.name','o.period_id','r.code as room','r.floor','b.program_id','b.year_level','b.id as section_id','p.name as professor')->get();
  $programs=DB::table('programs')->where('active',true)->get();$sections=DB::table('sections')->where('active',true)->get();
  $qualifications=DB::table('professor_subjects')->where('active',true)->get();$availability=DB::table('professor_availabilities')->where('active',true)->get();
  return view('scheduler',compact('offerings','rooms','professors','periods','schedules','programs','sections','qualifications','availability'));
 }
}
