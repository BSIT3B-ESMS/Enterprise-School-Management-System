<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class ActivityController extends Controller {
 public function index(){
  $query=DB::table('enrollment_events as a')->join('enrollments as e','e.id','=','a.enrollment_id')->join('students as s','s.id','=','e.student_id')->join('users as u','u.id','=','s.user_id')->select('a.*','u.name');
  if(auth()->user()->role==='professor')$query->where('a.user_id',auth()->id());
  if(auth()->user()->role==='student')$query->where('s.user_id',auth()->id());if(auth()->user()->role==='department_head')$query->whereIn('s.id',app(\App\Services\AccessService::class)->studentIds());$events=$query->orderByDesc('a.id')->paginate(20);return view('activity',compact('events'));
 }
 public function audit(){$logs=DB::table('audit_logs as a')->leftJoin('users as u','u.id','=','a.user_id')->select('a.*','u.name')->orderByDesc('a.id')->paginate(25);return view('audit',compact('logs'));}
}
