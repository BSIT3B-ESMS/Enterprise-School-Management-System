<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AvailabilityController extends Controller {
 public function room(Request $request,int $id){return $this->availability($request,$id,'room');}
 public function professor(Request $request,int $id){return $this->availability($request,$id,'professor');}
 private function availability(Request $request,int $id,string $type){
  $request->validate(['period_id'=>'required|integer|exists:periods,id','day'=>'nullable|in:Mon,Tue,Wed,Thu,Fri,Sat,Sun']);
  $resource=DB::table($type==='room'?'rooms':'professors')->find($id);abort_unless($resource,404);
  $meetings=DB::table('schedules as s')->join('offerings as o','o.id','=','s.offering_id')->where('s.'.$type.'_id',$id)->where(['o.period_id'=>$request->integer('period_id'),'s.active'=>true])->when($request->filled('day'),fn($q)=>$q->where('s.day',$request->string('day')))->select('s.day','s.start_time','s.end_time','s.offering_id')->orderBy('s.day')->orderBy('s.start_time')->get();
  $windows=$type==='professor'?DB::table('professor_availabilities')->where(['professor_id'=>$id,'active'=>true])->when($request->filled('day'),fn($q)=>$q->where('day',$request->string('day')))->get():collect();
  $closures=$type==='room'?DB::table('room_closures')->where(['room_id'=>$id,'period_id'=>$request->integer('period_id'),'active'=>true])->when($request->filled('day'),fn($q)=>$q->where('day',$request->string('day')))->get():collect();
  return response()->json(['id'=>$id,'active'=>(bool)$resource->active,'period_id'=>$request->integer('period_id'),'available_windows'=>$windows,'scheduled_meetings'=>$meetings,'closures'=>$closures,'note'=>'Schedule submission rechecks all conflicts, qualifications, permitted days and capacities.']);
 }
 public function prerequisites(int $id){abort_unless(DB::table('subjects')->where('id',$id)->exists(),404);return response()->json(DB::table('prerequisites as p')->join('subjects as s','s.id','=','p.required_subject_id')->where(['p.subject_id'=>$id,'p.active'=>true])->select('p.*','s.code','s.name')->get());}
 public function addPrerequisite(Request $request,int $id,\App\Services\AcademicService $service){$request->merge(['subject_id'=>$id]);return app(AcademicController::class)->store($request,'prerequisites',$service);}
}
