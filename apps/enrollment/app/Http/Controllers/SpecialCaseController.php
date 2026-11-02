<?php
namespace App\Http\Controllers;
use App\Services\{AcademicService,AcademicEvaluationService,AccessService,EnrollmentService,NotificationService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class SpecialCaseController extends Controller {
 public function defer(Request $request,int $id){$data=$request->validate(['reason'=>'required|string|max:2000']);DB::transaction(function()use($id,$data){$case=DB::table('special_cases')->find($id);abort_unless($case,404);app(AccessService::class)->student($case->student_id);app(\App\Services\TermWorkflowService::class)->mutable($case->period_id);if($case->status!=='pending')AcademicService::fail('Only a pending request can be deferred.');$ids=json_decode($case->requested_subject_ids??'[]',true);if(!$ids)AcademicService::fail('Select requested subjects before deferring.');$student=DB::table('students')->find($case->student_id);foreach($ids as $subject){$reason=app(\App\Services\WaitingListService::class)->reason($student,$subject,$case->period_id);if(!$reason)AcademicService::fail('An eligible requested class is available. Evaluate the request instead.');DB::table('subject_waiting_list')->updateOrInsert(['student_id'=>$student->id,'subject_id'=>$subject,'status'=>'waiting'],['requested_period_id'=>$case->period_id,'reason'=>$reason,'created_at'=>now(),'updated_at'=>now()]);}DB::table('special_cases')->where('id',$id)->update(['status'=>'deferred','evaluation'=>$data['reason'],'reviewer_id'=>auth()->id(),'updated_at'=>now()]);app(AcademicService::class)->audit('request_deferred','special_cases',$id,$data);app(NotificationService::class)->studentEvent($student->id,'Subjects deferred to waiting list','/waiting-list');});return back()->with('status','Request deferred. Waiting subjects will be checked when semesters open, schedules are saved or grades are posted.');}
 public function index(Request $request){
  $query=DB::table('special_cases as c')->join('students as s','s.id','=','c.student_id')->join('users as u','u.id','=','s.user_id')->join('periods as p','p.id','=','c.period_id')->select('c.*','u.name','p.name as period','s.classification');
  if($request->user()->role==='student')$query->where('s.user_id',$request->user()->id);
  if($request->user()->role==='department_head')$query->whereIn('s.id',app(AccessService::class)->studentIds());
  $rows=$query->orderByDesc('c.id')->paginate(15);if($request->expectsJson())return response()->json($rows);
  $periods=DB::table('periods')->where('active',true)->get();$subjects=collect();
  if($request->user()->role==='student'){$student=DB::table('students')->where('user_id',$request->user()->id)->first();if($student)$subjects=$this->requirements($student->curriculum_id);}
  return view('cases',compact('rows','periods','subjects'));
 }
 public function show(Request $request,int $id){
  $case=DB::table('special_cases')->find($id);abort_unless($case,404);app(AccessService::class)->student($case->student_id);
  $student=DB::table('students as s')->join('users as u','u.id','=','s.user_id')->where('s.id',$case->student_id)->select('s.*','u.name')->first();
  $subjects=$this->requirements($case->curriculum_id??$student->curriculum_id);
  $credits=DB::table('credit_evaluations as c')->join('subjects as s','s.id','=','c.subject_id')->where('special_case_id',$id)->select('c.*','s.code','s.name')->get();
  $requested=$subjects->whereIn('id',json_decode($case->requested_subject_ids??'[]',true));
  $period=DB::table('periods')->find($case->period_id);$reviewer=$case->reviewer_id?DB::table('users')->where('id',$case->reviewer_id)->value('name'):null;
  if($request->expectsJson())return response()->json(compact('case','student','credits','requested','reviewer'));
  $evaluatedAt=DB::table('audit_logs')->where(['entity'=>'special_cases','entity_id'=>$id,'action'=>'evaluated'])->value('created_at');
  $builder=app(\App\Services\SpecialScheduleService::class)->choices($student->id,$period->id);
  return view('case',compact('case','student','subjects','credits','requested','period','reviewer','evaluatedAt','builder'));
 }
 private function requirements(int $curriculumId){return DB::table('curriculum_subjects as c')->join('subjects as s','s.id','=','c.subject_id')->where(['c.curriculum_id'=>$curriculumId,'c.active'=>true])->select('s.*')->orderBy('s.code')->get();}
 public function store(Request $request,AcademicService $service){
  $data=$request->validate(['period_id'=>'required|exists:periods,id','request'=>'required|string|max:5000','requested_subject_ids'=>'nullable|array|max:50','requested_subject_ids.*'=>'integer|distinct|exists:subjects,id']);
  $id=DB::transaction(function()use($request,$data,$service){
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   $student=DB::table('students')->where('user_id',$request->user()->id)->lockForUpdate()->first();abort_unless($student && $student->active,403);
   if(!DB::table('periods')->where(['id'=>$data['period_id'],'active'=>true])->exists())AcademicService::fail('Select an active period.');
   app(\App\Services\TermWorkflowService::class)->mutable((int)$data['period_id']);
   if(DB::table('special_cases')->where(['student_id'=>$student->id,'period_id'=>$data['period_id'],'curriculum_id'=>$student->curriculum_id])->whereIn('status',['pending','approved'])->exists())AcademicService::fail('An open or approved evaluation already exists for this curriculum and period.');
   $requested=$data['requested_subject_ids']??[];
   if(count($requested)!==$this->requirements($student->curriculum_id)->whereIn('id',$requested)->count())AcademicService::fail('Requested subjects must belong to your assigned curriculum.');
   $id=DB::table('special_cases')->insertGetId(['period_id'=>$data['period_id'],'request'=>$data['request'],'requested_subject_ids'=>json_encode($requested),'student_id'=>$student->id,'curriculum_id'=>$student->curriculum_id,'created_at'=>now(),'updated_at'=>now()]);
   $service->audit('requested','special_cases',$id);app(NotificationService::class)->studentEvent($student->id,'Academic evaluation requested','/special-cases/'.$id);return $id;
  },3);
  return $request->expectsJson()?response()->json(['id'=>$id,'message'=>'Evaluation requested.'],201):redirect('/special-cases/'.$id)->with('status','Your evaluation request has been submitted.');
 }
 public function evaluate(Request $request,int $id,AcademicEvaluationService $service){
  if($request->route('decision'))$request->merge(['status'=>$request->route('decision')]);
  $data=$request->validate(['status'=>'required|in:approved,rejected','evaluation'=>'required|string|max:5000','credits'=>'nullable|array|max:100','credits.*.subject_id'=>'required|integer|distinct|exists:subjects,id','credits.*.source_institution'=>'required|string|max:255','credits.*.source_subject'=>'required|string|max:255','credits.*.source_grade'=>'nullable|string|max:30','credits.*.source_units'=>'required|numeric|min:0.01|max:100','credits.*.evidence_reference'=>'required|string|max:255','credits.*.reason'=>'required|string|max:2000','credits.*.decision'=>'required|in:approved,rejected']);
  $data['credits']=array_map(fn($c)=>array_intersect_key($c,array_flip(['subject_id','source_institution','source_subject','source_grade','source_units','evidence_reference','reason','decision'])),$data['credits']??[]);
  $service->evaluate($id,$data);
  return $request->expectsJson()?response()->json(['message'=>'Evaluation and subject decisions recorded.']):redirect('/special-cases/'.$id)->with('status','Evaluation and subject decisions recorded.');
 }
 public function forward(Request $request,int $id){
  $case=DB::table('special_cases')->find($id);abort_unless($case,404);app(AccessService::class)->student($case->student_id);
  $data=$request->validate(['offering_ids'=>'required|array|min:1','offering_ids.*'=>'integer|distinct|exists:offerings,id','action'=>'required|in:send,forward']);
  if($case->status!=='approved')AcademicService::fail('Approve the academic evaluation first.');
  $enrollmentId=app(EnrollmentService::class)->propose($case->student_id,$case->period_id,$data['offering_ids'],true,$data['action']==='forward');
  return redirect('/enrollments/'.$enrollmentId)->with('status',$data['action']==='forward'?'Assignments forwarded to Registrar.':'Proposal sent. Student acceptance will finalize enrollment and create the COR.');
 }
}
