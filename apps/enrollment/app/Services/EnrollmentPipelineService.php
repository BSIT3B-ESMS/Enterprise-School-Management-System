<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class EnrollmentPipelineService {
 public const LABELS=['new'=>'New','awaiting_evaluation'=>'Awaiting evaluation','special_ready'=>'Awaiting Registrar','proposed'=>'Proposal sent','revision_requested'=>'Revision requested','accepted'=>'Accepted','finalized'=>'Finalized'];
 public function rows(?int $periodId=null){
  $periodId??=app(TermWorkflowService::class)->current()?->id??0;
  $cases=DB::table('special_cases')->where('period_id',$periodId)->orderByDesc('id')->get()->unique(fn($case)=>$case->student_id.':'.$case->curriculum_id)->keyBy(fn($case)=>$case->student_id.':'.$case->curriculum_id);
  return DB::table('students as s')->join('users as u','u.id','=','s.user_id')->join('curriculums as c','c.id','=','s.curriculum_id')->join('programs as p','p.id','=','c.program_id')->leftJoin('enrollments as e',fn($j)=>$j->on('e.student_id','=','s.id')->where('e.period_id',$periodId))->where(['s.active'=>true,'u.active'=>true])->select('s.id','s.curriculum_id','s.classification','s.year_level','u.name','s.admission_id','p.code as program','e.status','e.accepted_version')->get()->map(function($row)use($cases){$case=$cases[$row->id.':'.$row->curriculum_id]??null;$row->stage=match($row->status){'finalized'=>'finalized','accepted','special_accepted'=>'accepted','special_ready'=>'special_ready','proposed','special_proposed'=>'proposed','revision_requested'=>'revision_requested',default=>$case?->status==='pending'?'awaiting_evaluation':($case?->status==='approved'?'special_ready':($case?->status==='rejected'?'rejected':'new'))};return $row;});
 }
 public function counts(?int $periodId=null):array {$rows=$this->rows($periodId);$counts=array_fill_keys(array_keys(self::LABELS),0);foreach($rows as $row){if(array_key_exists($row->stage,$counts))$counts[$row->stage]++;if($row->stage==='finalized')$counts['accepted']++;}return $counts;}
}
