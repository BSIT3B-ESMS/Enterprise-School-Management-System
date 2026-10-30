<?php
namespace App\Http\Controllers;
use App\Services\AcademicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
class AcademicController extends Controller {
 public function index(Request $request,string $resource) {
  $fields=config('academic.'.$resource);abort_unless(is_array($fields),404);
  $query=DB::table($resource);
  if($request->filled('search')){$search=mb_substr($request->string('search'),0,100);$query->where(function($q)use($fields,$search){foreach(array_keys($fields) as $field)$q->orWhere($field,'like','%'.$search.'%');});}
  $rows=$query->orderByDesc('id')->paginate(15)->withQueryString();
  if($resource==='sections'){
   $counts=DB::table('enrollments as e')->join('proposal_items as i','i.enrollment_id','=','e.id')->join('offerings as o','o.id','=','i.offering_id')->whereColumn('i.version','e.version')->where('e.status','finalized')->whereIn('o.section_id',$rows->pluck('id'))->groupBy('o.section_id')->select('o.section_id')->selectRaw('count(distinct e.student_id) as enrolled')->get()->keyBy('section_id');
   foreach($rows as $row)$row->enrolled_count=$counts[$row->id]->enrolled??0;
  }
  $choices=[];foreach($fields as $field=>$rules){if(preg_match('/exists:([^,]+),id/',$rules,$m)){$table=$m[1];$choices[$field]=DB::table($table)->when($table==='users',fn($q)=>$q->where('role','department_head'),fn($q)=>$q->where('active',true))->get()->mapWithKeys(fn($r)=>[$r->id=>($r->code??$r->name??$r->student_number??$r->admission_id??'Record').' #'.$r->id]);}elseif(preg_match('/(?:^|\|)in:([^|]+)/',$rules,$m))$choices[$field]=array_combine(explode(',',$m[1]),explode(',',$m[1]));elseif(str_contains($rules,'boolean'))$choices[$field]=[0=>'Required / No',1=>'Elective / Yes'];}
  foreach($fields as $field=>$rules){if(str_contains($rules,'exists:offerings,id'))$choices[$field]=DB::table('offerings as o')->join('subjects as s','s.id','=','o.subject_id')->join('sections as b','b.id','=','o.section_id')->where('o.active',true)->select('o.id','s.code','b.name')->get()->mapWithKeys(fn($r)=>[$r->id=>$r->code.' · '.$r->name.' #'.$r->id]);if(str_contains($rules,'exists:students,id'))$choices[$field]=DB::table('students as s')->join('users as u','u.id','=','s.user_id')->where('s.active',true)->select('s.id','u.name','s.admission_id')->get()->mapWithKeys(fn($r)=>[$r->id=>$r->name.' · '.$r->admission_id]);}
  $editing=$request->filled('edit')?DB::table($resource)->find($request->integer('edit')):null;
  return view('academic.index',compact('resource','fields','rows','choices','editing'));
 }
 public function store(Request $request,string $resource,AcademicService $service) {
  $fields=config('academic.'.$resource);abort_unless(is_array($fields),404);$data=$request->validate($fields);if($resource==='subjects' && array_diff(explode(',',$data['allowed_days']),['Mon','Tue','Wed','Thu','Fri','Sat','Sun']))AcademicService::fail('Allowed days must be comma-separated day codes, such as Mon,Wed,Fri.');if($resource==='rooms'){$floor=DB::table('floors')->find($data['floor_id']);$building=DB::table('buildings')->find($data['building_id']);if(!$building->active || !$floor->active || $floor->building_id!=$building->id)AcademicService::fail('Choose an active floor belonging to the selected building.');$data['building']=$building->name;$data['floor']=$floor->name;}
  try{DB::transaction(function()use($resource,$data,$service){
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   if($resource==='curriculum_subjects' && DB::table('students')->where('curriculum_id',$data['curriculum_id'])->exists())AcademicService::fail('This curriculum is assigned to students. Create a new version before changing its requirements.');
   // Serialize scheduling configuration changes on the academic period row.
   if(in_array($resource,['schedules','offerings'])){
    $period=$resource==='offerings'?$data['period_id']:DB::table('offerings')->find($data['offering_id'])->period_id;
    DB::table('periods')->where('id',$period)->lockForUpdate()->first();
   }
   if($resource==='department_head_programs' && DB::table('users')->find($data['user_id'])->role!=='department_head')AcademicService::fail('Select a Department Head account.');
   if($resource==='prerequisites'){DB::table('subjects')->orderBy('id')->lockForUpdate()->get();$service->prerequisite($data);}
   if($resource==='schedules'){
    if(DB::table('proposal_items')->where('offering_id',$data['offering_id'])->exists())AcademicService::fail('This class has proposal history. Schedule revisions require a new offering and revised student proposals.');
    $service->schedule($data);
   }
   if($resource==='offerings'){$section=DB::table('sections')->find($data['section_id']);if(!DB::table('periods')->where(['id'=>$data['period_id'],'active'=>true])->exists() || !DB::table('subjects')->where(['id'=>$data['subject_id'],'active'=>true])->exists())AcademicService::fail('Choose an active period and subject.');if(!$section->active || $section->period_id!=$data['period_id'] || $section->capacity<$data['capacity'])AcademicService::fail('Choose an active section in this period with sufficient capacity.');}
   $id=DB::table($resource)->insertGetId($data+['created_at'=>now(),'updated_at'=>now()]);$service->audit('created',$resource,$id,$data);if($resource==='academic_records')app(\App\Services\NotificationService::class)->studentEvent($data['student_id'],in_array($data['status'],['FAILED','INCOMPLETE','WITHDRAWN'])?'Back subject recorded':'Academic record recorded','/records/'.$data['student_id']);
  });}catch(QueryException $e){if(in_array((string)$e->getCode(),['23000','23505']))AcademicService::fail('This record duplicates an existing entry or references an invalid record.');throw $e;}
  return request()->expectsJson()?response()->json(['message'=>'Record created.'],201):back()->with('status','Record created.');
 }
 public function update(Request $request,string $resource,int $id,AcademicService $service){
  $fields=config('academic.'.$resource);abort_unless(is_array($fields),404);$data=$request->validate($fields);if($resource==='subjects' && array_diff(explode(',',$data['allowed_days']),['Mon','Tue','Wed','Thu','Fri','Sat','Sun']))AcademicService::fail('Allowed days must be comma-separated day codes, such as Mon,Wed,Fri.');if($resource==='rooms'){$floor=DB::table('floors')->find($data['floor_id']);$building=DB::table('buildings')->find($data['building_id']);if(!$building->active || !$floor->active || $floor->building_id!=$building->id)AcademicService::fail('Choose an active floor belonging to the selected building.');$data['building']=$building->name;$data['floor']=$floor->name;}
  try{DB::transaction(function()use($resource,$id,$data,$service){
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   $row=DB::table($resource)->where('id',$id)->lockForUpdate()->first();abort_unless($row,404);$this->assertMutable($resource,$row);
   if($resource==='prerequisites')$service->prerequisite($data,$id);
   if($resource==='schedules'){$this->assertMutable('offerings',DB::table('offerings')->find($data['offering_id']));$service->schedule($data,$id);}
   if($resource==='offerings'){$section=DB::table('sections')->find($data['section_id']);if(!DB::table('periods')->where(['id'=>$data['period_id'],'active'=>true])->exists() || !DB::table('subjects')->where(['id'=>$data['subject_id'],'active'=>true])->exists())AcademicService::fail('Choose an active period and subject.');if(!$section->active || $section->period_id!=$data['period_id'] || $section->capacity<$data['capacity'])AcademicService::fail('Offering period and capacity must match its section.');}
   if($resource==='department_head_programs' && DB::table('users')->find($data['user_id'])->role!=='department_head')AcademicService::fail('Select a Department Head account.');
   DB::table($resource)->where('id',$id)->update($data+['updated_at'=>now()]);$service->audit('updated',$resource,$id,['before'=>(array)$row,'after'=>$data]);if($resource==='academic_records')app(\App\Services\NotificationService::class)->studentEvent($data['student_id'],'Academic record updated','/records/'.$data['student_id']);
  },3);}catch(QueryException $e){if(in_array((string)$e->getCode(),['23000','23505']))AcademicService::fail('This duplicates an existing record or references invalid data.');throw $e;}
  return request()->expectsJson()?response()->json(['message'=>'Record updated.']):redirect('/academic/'.$resource)->with('status','Record updated. Previous values were retained in the audit history.');
 }
 public function deactivate(string $resource,int $id,AcademicService $service) {
  abort_unless(is_array(config('academic.'.$resource)),404);
  DB::transaction(function()use($resource,$id,$service){DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   $row=DB::table($resource)->where('id',$id)->lockForUpdate()->first();abort_unless($row,404);if(in_array($resource,['schedules','offerings','buildings','floors','curriculums','curriculum_subjects']))$this->assertMutable($resource,$row);DB::table($resource)->where('id',$id)->update(['active'=>false,'updated_at'=>now()]);$service->audit('deactivated',$resource,$id,(array)$row);},3);
  return back()->with('status','Record deactivated. History was retained.');
 }
 public function activate(string $resource,int $id,AcademicService $service){
  abort_unless(is_array(config('academic.'.$resource)),404);
  DB::transaction(function()use($resource,$id,$service){DB::table('periods')->orderBy('id')->lockForUpdate()->get();$row=DB::table($resource)->where('id',$id)->lockForUpdate()->first();abort_unless($row,404);
   if(in_array($resource,['schedules','offerings','curriculum_subjects']))$this->assertMutable($resource,$row);
   if($resource==='prerequisites'){DB::table('subjects')->orderBy('id')->lockForUpdate()->get();$service->prerequisite((array)$row,$id);}
   if($resource==='schedules')$service->schedule((array)$row,$id);
   DB::table($resource)->where('id',$id)->update(['active'=>true,'updated_at'=>now()]);$service->audit('activated',$resource,$id);
  },3);return request()->expectsJson()?response()->json(['message'=>'Record activated.']):back()->with('status','Record activated.');
 }
 private function assertMutable(string $resource,object $row):void {
  if(in_array($resource,['curriculums','curriculum_subjects']) && DB::table('students')->where('curriculum_id',$resource==='curriculums'?$row->id:$row->curriculum_id)->exists())AcademicService::fail('This curriculum is assigned to students. Create a new version to preserve historical requirements.');
  $query=DB::table('proposal_items as i')->join('offerings as o','o.id','=','i.offering_id');
  if($resource==='offerings')$query->where('o.id',$row->id);
  elseif($resource==='schedules')$query->where('o.id',$row->offering_id);
  elseif($resource==='subjects')$query->where('o.subject_id',$row->id);
  elseif($resource==='sections')$query->where('o.section_id',$row->id);
  elseif($resource==='periods')$query->where('o.period_id',$row->id);
  elseif($resource==='rooms')$query->join('schedules as s','s.offering_id','=','o.id')->where('s.room_id',$row->id);
  elseif(in_array($resource,['professor_subjects','professor_availabilities']))$query->join('schedules as s','s.offering_id','=','o.id')->where('s.professor_id',$row->professor_id);
  elseif($resource==='buildings' || $resource==='floors'){if(DB::table('rooms')->where($resource==='buildings'?'building_id':'floor_id',$row->id)->where('active',true)->exists())AcademicService::fail('Deactivate or relocate active rooms before changing their building or floor.');return;}else return;
  if($query->exists())AcademicService::fail('This record is part of proposal history. Use a new class offering and revised proposals to preserve student acceptance and historical schedules.');
 }
}