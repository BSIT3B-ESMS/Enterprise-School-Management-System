<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\{AccessService,AcademicService,AcademicEvaluationService};
class RecordsController extends Controller {
 public function index(Request $request,AccessService $access){
  $request->validate(['search'=>'nullable|string|max:100','classification'=>'nullable|in:regular,irregular,transferee,returning']);
  $query=DB::table('students as s')->join('users as u','u.id','=','s.user_id')->join('curriculums as c','c.id','=','s.curriculum_id')->select('s.*','u.name','u.email','c.name as curriculum');
  if($request->user()->role==='student')$query->where('s.user_id',$request->user()->id);
  if($request->user()->role==='department_head')$query->whereIn('s.id',$access->studentIds());
  if($request->filled('search')){$term='%'.mb_substr($request->string('search'),0,100).'%';$query->where(fn($q)=>$q->where('u.name','like',$term)->orWhere('s.student_number','like',$term)->orWhere('s.admission_id','like',$term));}
  if(in_array($request->input('classification'),['regular','irregular','transferee','returning'],true))$query->where('s.classification',$request->input('classification'));
  $students=\App\Support\TableQuery::sort($query,$request,['name'=>'u.name','admission_id'=>'s.admission_id','curriculum'=>'c.name','classification'=>'s.classification','year_level'=>'s.year_level'],'name')->orderBy('s.id')->paginate(15)->withQueryString();return view('records',compact('students'));
 }
 public function show(int $id,AccessService $access){
  $access->student($id);$student=DB::table('students as s')->join('users as u','u.id','=','s.user_id')->where('s.id',$id)->select('s.*','u.name')->first();
  $records=DB::table('academic_records as r')->join('subjects as s','s.id','=','r.subject_id')->join('periods as p','p.id','=','r.period_id')->where('r.student_id',$id)->select('r.*','s.code','s.name','p.name as period')->get();
  $requirements=DB::table('curriculum_subjects as c')->join('subjects as s','s.id','=','c.subject_id')->where(['c.curriculum_id'=>$student->curriculum_id,'c.active'=>true])->select('s.*','c.year_level','c.semester','c.elective')->orderBy('c.year_level')->orderBy('c.semester')->get();
  $periods=DB::table('periods')->where('active',true)->get();$overrides=DB::table('academic_overrides')->where('student_id',$id)->get();
  $curriculum=DB::table('curriculums')->find($student->curriculum_id);
  $curriculums=DB::table('curriculums')->where(['program_id'=>$curriculum->program_id,'active'=>true])->get();
  $assignments=DB::table('curriculum_assignments as a')->join('curriculums as c','c.id','=','a.curriculum_id')->join('curriculums as old','old.id','=','a.previous_curriculum_id')->join('users as u','u.id','=','a.user_id')->where('student_id',$id)->select('a.*','c.name as curriculum','old.name as previous','u.name as registrar')->orderByDesc('a.id')->get();
  $credits=DB::table('credit_evaluations as c')->join('special_cases as e','e.id','=','c.special_case_id')->join('subjects as s','s.id','=','c.subject_id')->where('e.student_id',$id)->select('c.*','s.code')->get();
  $prerequisites=DB::table('prerequisites as p')->join('subjects as s','s.id','=','p.required_subject_id')->whereIn('p.subject_id',$requirements->pluck('id'))->where('p.active',true)->select('p.*','s.code')->get()->groupBy('subject_id');
  return view('record',compact('student','records','requirements','periods','overrides','curriculum','curriculums','assignments','credits','prerequisites'));
 }
 public function assign(Request $request,int $id,AcademicEvaluationService $service){
  $data=$request->validate(['curriculum_id'=>'required|integer|exists:curriculums,id','previous_curriculum_id'=>'required|integer|exists:curriculums,id','reason'=>'required|string|min:20|max:3000']);
  $service->assignCurriculum($id,$data);
  return $request->expectsJson()?response()->json(['message'=>'Curriculum assignment recorded.']):back()->with('status','Curriculum assignment recorded. Previous requirements and CORs are preserved.');
 }
 public function update(Request $request,int $id,AcademicService $service){
  $data=$request->validate(['classification'=>'required|in:regular,irregular,transferee,returning','year_level'=>'required|integer|min:1|max:8','student_number'=>['nullable','string','max:50',\Illuminate\Validation\Rule::unique('students','student_number')->ignore($id)]]);
  DB::transaction(function()use($id,$data,$service){
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   $row=DB::table('students')->where('id',$id)->lockForUpdate()->first();abort_unless($row,404);
   if(($row->classification!==$data['classification'] || $row->year_level!=(int)$data['year_level']) && DB::table('enrollments')->where('student_id',$id)->whereNotIn('status',['finalized','term_closed'])->exists())AcademicService::fail('Resolve open enrollment proposals before changing classification or year level.');
   DB::table('students')->where('id',$id)->update($data+['updated_at'=>now()]);$service->audit('classification_updated','students',$id,['before'=>(array)$row,'after'=>$data]);
  },3);return back()->with('status','Student details updated.');
 }
}
