<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller {
 public function index(){
  $role=auth()->user()->role;$portal=match($role){'registrar'=>'Registrar','department_head'=>'Department Head',default=>'Student'};
  $students=DB::table('students');$enrollments=DB::table('enrollments');$cases=DB::table('special_cases');$student=null;$recent=collect();$schedule=collect();$notices=collect();$backSubjects=collect();
  if($role==='student'){
   $student=DB::table('students as s')->join('curriculums as c','c.id','=','s.curriculum_id')->join('programs as p','p.id','=','c.program_id')->where('s.user_id',auth()->id())->select('s.*','p.name as program','c.name as curriculum')->first();$id=$student?->id??0;
   $students->where('id',$id);$enrollments->where('student_id',$id);$cases->where('student_id',$id);
   $recent=DB::table('enrollments as e')->join('periods as p','p.id','=','e.period_id')->where('e.student_id',$id)->select('e.*','p.name as period')->orderByDesc('e.updated_at')->limit(5)->get();
   $schedule=DB::table('enrollments as e')->join('periods as p','p.id','=','e.period_id')->join('proposal_items as i',function($j){$j->on('i.enrollment_id','=','e.id')->on('i.version','=','e.version');})->join('offerings as o','o.id','=','i.offering_id')->join('subjects as sub','sub.id','=','o.subject_id')->join('schedules as c','c.offering_id','=','o.id')->join('rooms as r','r.id','=','c.room_id')->join('professors as prof','prof.id','=','c.professor_id')->where(['e.student_id'=>$id,'e.status'=>'finalized','p.active'=>true,'c.active'=>true])->select('sub.code','sub.name','c.day','c.start_time','c.end_time','r.code as room','prof.name as professor','p.name as period')->orderBy('c.day')->orderBy('c.start_time')->get();
   $notices=DB::table('portal_notifications')->where('user_id',auth()->id())->orderByDesc('id')->limit(5)->get();
   if($student){$completed=DB::table('academic_records')->where(['student_id'=>$id,'active'=>true])->whereIn('status',['PASSED','CREDITED'])->pluck('subject_id');$failed=DB::table('academic_records')->where(['student_id'=>$id,'active'=>true])->whereIn('status',['FAILED','INCOMPLETE','WITHDRAWN'])->pluck('subject_id');$backSubjects=DB::table('curriculum_subjects as c')->join('subjects as s','s.id','=','c.subject_id')->where(['c.curriculum_id'=>$student->curriculum_id,'c.active'=>true])->whereNotIn('s.id',$completed)->where(fn($q)=>$q->where('c.year_level','<',$student->year_level)->orWhereIn('s.id',$failed))->select('s.code','s.name')->get();}
  }
  if($role==='department_head'){$ids=app(\App\Services\AccessService::class)->studentIds();$students->whereIn('id',$ids);$enrollments->whereIn('student_id',$ids);$cases->whereIn('student_id',$ids);}
  $metrics=['Student records'=>(clone $students)->count(),'Pending proposals'=>(clone $enrollments)->whereIn('status',['proposed','special_proposed'])->count(),'Finalized enrollments'=>(clone $enrollments)->where('status','finalized')->count(),'Pending evaluations'=>(clone $cases)->where('status','pending')->count()];
  if($role==='registrar'){
   foreach(['regular','irregular','transferee','returning'] as $type)$metrics[ucfirst($type).' students']=(clone $students)->where('classification',$type)->count();
   $counts=DB::table('enrollments as e')->join('proposal_items as i',function($j){$j->on('i.enrollment_id','=','e.id')->on('i.version','=','e.version');})->join('offerings as o','o.id','=','i.offering_id')->where('e.status','finalized')->groupBy('o.section_id')->select('o.section_id')->selectRaw('count(distinct e.student_id) as enrolled')->get()->keyBy('section_id');
   $sections=DB::table('sections')->where('active',true)->get();$full=$sections->filter(fn($s)=>($counts[$s->id]->enrolled??0)>=$s->capacity)->count();
   $metrics+=['Full sections'=>$full,'Available sections'=>$sections->count()-$full,'Active subjects'=>DB::table('subjects')->where('active',true)->count(),'Available rooms'=>DB::table('rooms')->where('active',true)->count(),'Assigned professors'=>DB::table('schedules')->where('active',true)->distinct()->count('professor_id'),'Accepted awaiting finalization'=>(clone $enrollments)->where('status','accepted')->count(),'Finalized missing COR'=>DB::table('enrollments as e')->where('e.status','finalized')->whereNotExists(fn($q)=>$q->selectRaw('1')->from('cor_versions as c')->whereColumn('c.enrollment_id','e.id')->whereColumn('c.version','e.version'))->count()];
  }
  if($role==='department_head'){
   foreach(['irregular','transferee','returning'] as $type)$metrics[ucfirst($type).' pending evaluations']=(clone $cases)->where('status','pending')->whereIn('student_id',(clone $students)->where('classification',$type)->select('id'))->count();
   $metrics+=['Back-subject requests'=>(clone $cases)->where('status','pending')->whereNotNull('requested_subject_ids')->where('requested_subject_ids','!=','[]')->count(),'Approved evaluations'=>(clone $cases)->where('status','approved')->count(),'Rejected requests'=>(clone $cases)->where('status','rejected')->count(),'Accepted special assignments'=>(clone $enrollments)->where('status','special_accepted')->count()];
  }
  $description=$role==='student'?'Your academic information, enrollment decisions and class schedule.':'Enrollment totals across recorded periods; class and section availability uses active records.';
  $pipelinePeriod=app(\App\Services\TermWorkflowService::class)->current();$pipeline=$role==='registrar'?app(\App\Services\EnrollmentPipelineService::class)->counts($pipelinePeriod?->id):[];
  return view('dashboard',compact('portal','description','metrics','student','recent','schedule','notices','backSubjects','pipeline','pipelinePeriod'));
 }
 public function professors(\Illuminate\Http\Request $request){
  $request->validate(['search'=>'nullable|string|max:100','department'=>'nullable|string|max:100','employment_type'=>'nullable|in:FULL_TIME,PART_TIME','active'=>'nullable|in:0,1']);
  $query=DB::table('professors');
  if($request->filled('search')){$term='%'.$request->input('search').'%';$query->where(fn($q)=>$q->where('name','like',$term)->orWhere('employee_id','like',$term)->orWhere('email','like',$term));}
  foreach(['department','employment_type','active'] as $field)if($request->filled($field))$query->where($field,$request->input($field));
  $professors=\App\Support\TableQuery::sort($query,$request,array_combine(['name','employee_id','department','employment_type','active','last_synced_at'],['name','employee_id','department','employment_type','active','last_synced_at']),'name')->paginate(15)->withQueryString();
  $ids=$professors->pluck('id');
  $qualifications=DB::table('professor_subjects as q')->join('subjects as s','s.id','=','q.subject_id')->whereIn('q.professor_id',$ids)->where('q.active',true)->select('q.professor_id','s.code','s.name')->get()->groupBy('professor_id');
  $availability=DB::table('professor_availabilities')->whereIn('professor_id',$ids)->where('active',true)->orderBy('day')->orderBy('start_time')->get()->groupBy('professor_id');
  $departments=DB::table('professors')->distinct()->orderBy('department')->pluck('department');
  return view('professors',compact('professors','qualifications','availability','departments'));
 }
}
