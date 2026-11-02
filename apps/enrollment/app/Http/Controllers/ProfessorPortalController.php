<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ProfessorPortalController extends Controller {
 public function index(Request $request){
  $professor=DB::table('professors')->where(['user_id'=>$request->user()->id,'active'=>true])->first();
  $offerings=$professor?DB::table('schedules as m')->join('offerings as o','o.id','=','m.offering_id')->join('subjects as s','s.id','=','o.subject_id')->join('sections as b','b.id','=','o.section_id')->join('periods as p','p.id','=','o.period_id')->where(['m.professor_id'=>$professor->id,'m.active'=>true])->select('o.id','s.code','s.name','b.name as section','p.name as period','p.closed_at')->distinct()->orderBy('p.name')->get():collect();
  $selected=$offerings->firstWhere('id',$request->integer('offering_id'))??$offerings->first();$students=collect();
  if($selected){$offering=DB::table('offerings')->find($selected->id);$students=DB::table('enrollments as e')->join('proposal_items as i',fn($j)=>$j->on('i.enrollment_id','=','e.id')->on('i.version','=','e.version'))->join('students as s','s.id','=','e.student_id')->join('users as u','u.id','=','s.user_id')->leftJoin('academic_records as r',fn($j)=>$j->on('r.student_id','=','s.id')->where('r.subject_id',$offering->subject_id)->where('r.period_id',$offering->period_id))->where(['i.offering_id'=>$selected->id,'e.status'=>'finalized'])->select('s.id','s.student_number','s.admission_id','u.name','r.grade','r.status')->orderBy('u.name')->get();}
  return view('professor-portal',compact('professor','offerings','selected','students'));
 }
 public function submit(Request $request,int $id,\App\Services\GradeService $service){$data=$request->validate(['grades'=>'required|array|max:500','grades.*'=>'nullable|in:1.00,1.25,1.50,1.75,2.00,2.25,2.50,2.75,3.00,5.00,INC']);$count=$service->submit($id,$data['grades']);return back()->with('status',$count.' grade(s) submitted. Students have been notified.');}
 public function grades(Request $request){
  $student=DB::table('students')->where('user_id',$request->user()->id)->first();abort_unless($student,404);
  $requirements=DB::table('curriculum_subjects as c')->join('subjects as s','s.id','=','c.subject_id')->where(['c.curriculum_id'=>$student->curriculum_id,'c.active'=>true])->select('s.*','c.year_level','c.semester')->orderBy('c.year_level')->orderBy('c.semester')->orderBy('s.code')->get();
  $records=DB::table('academic_records as r')->leftJoin('users as u','u.id','=','r.submitted_by')->join('periods as p','p.id','=','r.period_id')->where(['r.student_id'=>$student->id,'r.active'=>true])->select('r.*','u.name as professor','p.name as period')->orderByDesc('r.id')->get()->groupBy('subject_id');
  $period=app(\App\Services\TermWorkflowService::class)->current();$semester=(int)($period?->semester??DB::table('periods')->orderByDesc('id')->value('semester')??1);
  $prerequisites=DB::table('prerequisites as p')->join('subjects as s','s.id','=','p.required_subject_id')->where('p.active',true)->whereIn('p.subject_id',$requirements->pluck('id'))->select('p.*','s.code')->orderBy('s.code')->get()->groupBy('subject_id');
  $summary=['Passed / credited'=>0,'Failed / incomplete'=>0,'No grade yet'=>0,'Subjects in program'=>$requirements->count()];
  foreach($requirements as $subject){$attempts=$records[$subject->id]??collect();$record=$attempts->first(fn($r)=>in_array($r->status,['PASSED','CREDITED']))??$attempts->first();$subject->result=$record;$subject->upcoming=!$record && ($subject->year_level>$student->year_level || ($subject->year_level===$student->year_level && $subject->semester>$semester));if($record&&in_array($record->status,['PASSED','CREDITED']))$summary['Passed / credited']++;elseif($record&&in_array($record->status,['FAILED','INCOMPLETE']))$summary['Failed / incomplete']++;elseif(!$subject->upcoming)$summary['No grade yet']++;}
  return view('grades',compact('student','requirements','records','summary','prerequisites'));
 }
}
