<?php
namespace App\Http\Controllers;
use App\Services\AcademicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class SpecialCaseController extends Controller {
 public function index(Request $request){
  $query=DB::table('special_cases as c')->join('students as s','s.id','=','c.student_id')->join('users as u','u.id','=','s.user_id')->join('periods as p','p.id','=','c.period_id')->select('c.*','u.name','p.name as period','s.classification');
  if($request->user()->role==='student')$query->where('s.user_id',$request->user()->id);
  if($request->user()->role==='department_head')$query->whereIn('s.id',app(\App\Services\AccessService::class)->studentIds());
  $rows=$query->orderByDesc('c.id')->paginate(15);if($request->expectsJson())return response()->json($rows);$periods=DB::table('periods')->where('active',true)->get();return view('cases',compact('rows','periods'));
 }
 public function store(Request $request,AcademicService $service){$data=$request->validate(['period_id'=>'required|exists:periods,id','request'=>'required|string|max:5000']);$student=DB::table('students')->where('user_id',$request->user()->id)->first();abort_unless($student,403);$id=DB::table('special_cases')->insertGetId($data+['student_id'=>$student->id,'created_at'=>now(),'updated_at'=>now()]);$service->audit('requested','special_cases',$id);app(\App\Services\NotificationService::class)->studentEvent($student->id,'Academic evaluation requested','/special-cases');return $request->expectsJson()?response()->json(['id'=>$id,'message'=>'Evaluation requested.'],201):back()->with('status','Your academic evaluation request has been submitted.');}
 public function evaluate(Request $request,int $id,AcademicService $service){$data=$request->validate(['status'=>'required|in:approved,rejected','evaluation'=>'required|string|max:5000']);DB::transaction(function()use($request,$id,$data,$service){$case=DB::table('special_cases')->where('id',$id)->lockForUpdate()->first();abort_unless($case,404);app(\App\Services\AccessService::class)->student($case->student_id);if($case->status!=='pending')AcademicService::fail('This request has already been evaluated.');DB::table('special_cases')->where('id',$id)->update($data+['reviewer_id'=>$request->user()->id,'updated_at'=>now()]);$service->audit('evaluated','special_cases',$id,$data);app(\App\Services\NotificationService::class)->studentEvent($case->student_id,'Academic evaluation '.$data['status'],'/special-cases');});return $request->expectsJson()?response()->json(['message'=>'Evaluation recorded.']):back()->with('status','Academic evaluation recorded.');}
}