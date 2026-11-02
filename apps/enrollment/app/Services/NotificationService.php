<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class NotificationService {
 public function accountEvent(int $userId,string $title):void {DB::table('portal_notifications')->insert(['user_id'=>$userId,'title'=>$title,'url'=>'/account/password','created_at'=>now(),'updated_at'=>now()]);}
 public function studentEvent(int $studentId,string $title,string $url):void {
  $student=DB::table('students')->find($studentId);$program=DB::table('curriculums')->where('id',$student->curriculum_id)->value('program_id');
  $recipients=DB::table('users')->where('role','registrar')->pluck('id')->push($student->user_id)->merge(DB::table('department_head_programs')->where(['program_id'=>$program,'active'=>true])->pluck('user_id'))->unique();
  foreach($recipients as $userId)DB::table('portal_notifications')->insert(['user_id'=>$userId,'title'=>$title,'url'=>$userId!==$student->user_id && in_array($url,['/grades','/waiting-list'])?'/records/'.$studentId:$url,'created_at'=>now(),'updated_at'=>now()]);
 }
}
