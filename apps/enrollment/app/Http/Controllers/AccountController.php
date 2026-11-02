<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class AccountController extends Controller {
 public function show(){
  $user=auth()->user();$details=['Name'=>$user->name,'Email'=>$user->email,'Role'=>ucwords(str_replace('_',' ',$user->role))];
  $period=app(\App\Services\TermWorkflowService::class)->current();
  if($user->role==='student'){$student=DB::table('students as s')->join('curriculums as c','c.id','=','s.curriculum_id')->join('programs as p','p.id','=','c.program_id')->leftJoin('colleges as college','college.id','=','p.college_id')->where('s.user_id',$user->id)->select('s.*','p.code as program_code','p.name as program','college.name as college')->first();if($student){$section=DB::table('student_sections as m')->join('sections as s','s.id','=','m.section_id')->where(['m.student_id'=>$student->id,'m.period_id'=>$period?->id??0])->value('s.name');$status=DB::table('enrollments')->where(['student_id'=>$student->id,'period_id'=>$period?->id??0])->value('status');$details+=['Student ID'=>$student->student_number??$student->id,'Admission ID'=>$student->admission_id,'College'=>$student->college??'Not assigned','Program'=>$student->program_code.' · '.$student->program,'Year level'=>$student->year_level,'Classification'=>ucfirst($student->classification),'Section'=>$section??'Unassigned','Academic semester'=>$period?->name??'No open semester','Enrollment status'=>$status?ucwords(str_replace('_',' ',$status)):'Waiting for proposal'];}}
  if($user->role==='professor'){$professor=DB::table('professors')->where('user_id',$user->id)->first();if($professor){$subjects=DB::table('professor_subjects as a')->join('subjects as s','s.id','=','a.subject_id')->where(['a.professor_id'=>$professor->id,'a.active'=>true])->orderBy('s.code')->pluck('s.code');$details+=['Employee ID'=>$professor->employee_id,'Employment'=>str_replace('_',' ',$professor->employment_type),'Employment status'=>$professor->active?'Active':'Inactive','Department'=>$professor->department,'Authorized subjects'=>$subjects->implode(', ')?:'None assigned'];}else $details['EMS record']='Not linked';}
  if($user->role==='department_head')$details['Assigned programs']=DB::table('department_head_programs as a')->join('programs as p','p.id','=','a.program_id')->where(['a.user_id'=>$user->id,'a.active'=>true])->pluck('p.code')->implode(', ')?:'None assigned';
  return view('auth.password',compact('details'));
 }
}
