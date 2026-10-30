<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller {
 public function index(){
  $role=auth()->user()->role;$portal=match($role){'registrar'=>'Registrar','department_head'=>'Department Head',default=>'Student'};
  $students=DB::table('students');$enrollments=DB::table('enrollments');$cases=DB::table('special_cases');
  if($role==='student'){$id=DB::table('students')->where('user_id',auth()->id())->value('id');$students->where('id',$id??0);$enrollments->where('student_id',$id??0);$cases->where('student_id',$id??0);}
  if($role==='department_head'){$ids=app(\App\Services\AccessService::class)->studentIds();$students->whereIn('id',$ids);$enrollments->whereIn('student_id',$ids);$cases->whereIn('student_id',$ids);}
  $metrics=['Student records'=>(clone $students)->count(),'Pending proposals'=>(clone $enrollments)->whereIn('status',['proposed','special_proposed'])->count(),'Finalized enrollments'=>(clone $enrollments)->where('status','finalized')->count(),'Pending evaluations'=>(clone $cases)->where('status','pending')->count()];
  if($role==='registrar')$metrics+=['Active subjects'=>DB::table('subjects')->where('active',true)->count(),'Full class offerings'=>DB::table('offerings')->where('active',true)->whereColumn('enrolled_count','>=','capacity')->count(),'Available rooms'=>DB::table('rooms')->where('active',true)->count(),'Active professors'=>DB::table('professors')->where('active',true)->count()];
  $description='Review enrollment progress and continue your work below.';return view('dashboard',compact('portal','description','metrics'));
 }
 public function professors(){$professors=DB::table('professors')->orderBy('name')->paginate(20);return view('professors',compact('professors'));}
}