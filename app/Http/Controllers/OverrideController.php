<?php
namespace App\Http\Controllers;
use App\Services\AccessService;
use App\Services\AcademicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class OverrideController extends Controller {
public function store(Request $request,AccessService $access,AcademicService $service){$data=$request->validate(['student_id'=>'required|exists:students,id','subject_id'=>'required|exists:subjects,id','period_id'=>'required|exists:periods,id','reason'=>'required|string|min:20|max:3000']);$access->student($data['student_id']);if(DB::table('academic_overrides')->where(['student_id'=>$data['student_id'],'subject_id'=>$data['subject_id'],'period_id'=>$data['period_id']])->exists())AcademicService::fail('An override is already recorded for this student, subject and period.');$id=DB::table('academic_overrides')->insertGetId($data+['approved_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);$service->audit('prerequisite_override_approved','academic_overrides',$id,$data);return back()->with('status','Authorized prerequisite override recorded. Curriculum membership, completed-subject, schedule and capacity rules still apply.');}
}