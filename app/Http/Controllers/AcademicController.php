<?php
namespace App\Http\Controllers;
use App\Services\AcademicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
class AcademicController extends Controller {
 public function index(Request $request,string $resource) {
  $request->validate(['search'=>'nullable|string|max:100']);
  $fields=config('academic.'.$resource);abort_unless(is_array($fields),404);
  $query=DB::table($resource);
  if($resource==='subjects'){$request->validate(['scope'=>'nullable|in:general,college,program','college_id'=>'nullable|integer|exists:colleges,id','program_id'=>'nullable|integer|exists:programs,id']);foreach(['scope','college_id','program_id'] as $filter)if($request->filled($filter))$query->where($filter,$request->input($filter));}
  if(in_array($request->query('active'),['0','1'],true))$query->where('active',$request->query('active'));
  if($request->filled('search')){$search=mb_substr($request->string('search'),0,100);$query->where(function($q)use($fields,$search){foreach(array_keys($fields) as $field)$q->orWhere($field,'like','%'.$search.'%');});}
  $sortFields=array_merge(['id','active'],array_keys($fields));
  $rows=\App\Support\TableQuery::sort($query,$request,array_combine($sortFields,$sortFields),'id','desc')->orderBy('id')->paginate(15)->withQueryString();
  if($resource==='sections'){
   $counts=DB::table('enrollments as e')->join('proposal_items as i','i.enrollment_id','=','e.id')->join('offerings as o','o.id','=','i.offering_id')->whereColumn('i.version','e.version')->where('e.status','finalized')->whereIn('o.section_id',$rows->pluck('id'))->groupBy('o.section_id')->select('o.section_id')->selectRaw('count(distinct e.student_id) as enrolled')->get()->keyBy('section_id');
   foreach($rows as $row)$row->enrolled_count=$counts[$row->id]->enrolled??0;
  }
  $choices=[];foreach($fields as $field=>$rules){if(preg_match('/exists:([^,]+),id/',$rules,$m)){$table=$m[1];$choices[$field]=DB::table($table)->when($table==='users',fn($q)=>$q->where('role','department_head'),fn($q)=>$q->where('active',true))->get()->mapWithKeys(fn($r)=>[$r->id=>($r->code??$r->name??$r->student_number??$r->admission_id??'Record').' #'.$r->id]);}elseif(preg_match('/(?:^|\|)in:([^|]+)/',$rules,$m))$choices[$field]=array_combine(explode(',',$m[1]),explode(',',$m[1]));elseif(str_contains($rules,'boolean'))$choices[$field]=[0=>'Required / No',1=>'Elective / Yes'];}
  foreach($fields as $field=>$rules){if(str_contains($rules,'exists:offerings,id'))$choices[$field]=DB::table('offerings as o')->join('subjects as s','s.id','=','o.subject_id')->join('sections as b','b.id','=','o.section_id')->where('o.active',true)->select('o.id','s.code','b.name')->get()->mapWithKeys(fn($r)=>[$r->id=>$r->code.' · '.$r->name.' #'.$r->id]);if(str_contains($rules,'exists:students,id'))$choices[$field]=DB::table('students as s')->join('users as u','u.id','=','s.user_id')->where('s.active',true)->select('s.id','u.name','s.admission_id')->get()->mapWithKeys(fn($r)=>[$r->id=>$r->name.' · '.$r->admission_id]);}
  $editing=$request->filled('edit')?DB::table($resource)->find($request->integer('edit')):null;
  $prerequisiteChoices=$resource==='subjects'?DB::table('subjects')->where('active',true)->when($editing,fn($q)=>$q->where('id','!=',$editing->id))->orderBy('code')->get():collect();
  $selectedPrerequisites=$resource==='subjects' && $editing?DB::table('prerequisites')->where(['subject_id'=>$editing->id,'active'=>true])->pluck('required_subject_id')->all():[];
  $defaultCapacity=app(\App\Services\SettingsService::class)->get('default_section_capacity','40');
  return view('academic.index',compact('resource','fields','rows','choices','editing','prerequisiteChoices','selectedPrerequisites','defaultCapacity'));
 }
 public function store(Request $request,string $resource,AcademicService $service) {
  $fields=config('academic.'.$resource);abort_unless(is_array($fields),404);$data=$request->validate($fields);if($resource==='subjects' && array_diff(explode(',',$data['allowed_days']),['Mon','Tue','Wed','Thu','Fri','Sat','Sun']))AcademicService::fail('Allowed days must be comma-separated day codes, such as Mon,Wed,Fri.');if($resource==='rooms'){$floor=DB::table('floors')->find($data['floor_id']);$building=DB::table('buildings')->find($data['building_id']);if(!$building->active || !$floor->active || $floor->building_id!=$building->id)AcademicService::fail('Choose an active floor belonging to the selected building.');$data['building']=$building->name;$data['floor']=$floor->name;}
  $prerequisites=$this->prerequisiteInput($request,$resource);
  try{DB::transaction(function()use($resource,$data,$service,$prerequisites){
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   $this->workflowGuard($resource,(object)$data);
   if($resource==='curriculum_subjects' && $this->curriculumUsed($data['curriculum_id']))AcademicService::fail('This curriculum is assigned to students. Create a new version before changing its requirements.');
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
   if($prerequisites!==null)$this->syncPrerequisites($id,$prerequisites,$service);
  });}catch(QueryException $e){if(in_array((string)$e->getCode(),['23000','23505']))AcademicService::fail('This record duplicates an existing entry or references an invalid record.');throw $e;}
  return request()->expectsJson()?response()->json(['message'=>'Record created.'],201):back()->with('status','Record created.');
 }
 public function update(Request $request,string $resource,int $id,AcademicService $service){
  $fields=config('academic.'.$resource);abort_unless(is_array($fields),404);$data=$request->validate($fields);if($resource==='subjects' && array_diff(explode(',',$data['allowed_days']),['Mon','Tue','Wed','Thu','Fri','Sat','Sun']))AcademicService::fail('Allowed days must be comma-separated day codes, such as Mon,Wed,Fri.');if($resource==='rooms'){$floor=DB::table('floors')->find($data['floor_id']);$building=DB::table('buildings')->find($data['building_id']);if(!$building->active || !$floor->active || $floor->building_id!=$building->id)AcademicService::fail('Choose an active floor belonging to the selected building.');$data['building']=$building->name;$data['floor']=$floor->name;}
  $prerequisites=$this->prerequisiteInput($request,$resource);
  try{DB::transaction(function()use($resource,$id,$data,$service,$prerequisites){
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   $row=DB::table($resource)->where('id',$id)->lockForUpdate()->first();abort_unless($row,404);$this->assertMutable($resource,$row);
   $this->workflowGuard($resource,(object)$data);
   if($resource==='curriculum_subjects' && $this->curriculumUsed($data['curriculum_id']))AcademicService::fail('The target curriculum has historical assignments. Create a new version.');
   if($resource==='prerequisites')$service->prerequisite($data,$id);
   if($resource==='schedules'){$this->assertMutable('offerings',DB::table('offerings')->find($data['offering_id']));$service->schedule($data,$id);}
   if($resource==='offerings'){$section=DB::table('sections')->find($data['section_id']);if(!DB::table('periods')->where(['id'=>$data['period_id'],'active'=>true])->exists() || !DB::table('subjects')->where(['id'=>$data['subject_id'],'active'=>true])->exists())AcademicService::fail('Choose an active period and subject.');if(!$section->active || $section->period_id!=$data['period_id'] || $section->capacity<$data['capacity'])AcademicService::fail('Offering period and capacity must match its section.');}
   if($resource==='department_head_programs' && DB::table('users')->find($data['user_id'])->role!=='department_head')AcademicService::fail('Select a Department Head account.');
   DB::table($resource)->where('id',$id)->update($data+['updated_at'=>now()]);$service->audit('updated',$resource,$id,['before'=>(array)$row,'after'=>$data]);if($resource==='academic_records')app(\App\Services\NotificationService::class)->studentEvent($data['student_id'],'Academic record updated','/records/'.$data['student_id']);
   if($prerequisites!==null)$this->syncPrerequisites($id,$prerequisites,$service);
  },3);}catch(QueryException $e){if(in_array((string)$e->getCode(),['23000','23505']))AcademicService::fail('This duplicates an existing record or references invalid data.');throw $e;}
  return request()->expectsJson()?response()->json(['message'=>'Record updated.']):redirect('/academic/'.$resource)->with('status','Record updated. Previous values were retained in the audit history.');
 }
 public function deactivate(string $resource,int $id,AcademicService $service) {
  abort_unless(is_array(config('academic.'.$resource)),404);
  DB::transaction(function()use($resource,$id,$service){DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   if($resource==='colleges' && DB::table('programs')->where(['college_id'=>$id,'active'=>true])->exists())AcademicService::fail('Move or deactivate this college’s programs first.');
   if($resource==='programs' && (DB::table('students')->whereIn('curriculum_id',DB::table('curriculums')->where('program_id',$id)->select('id'))->exists() || DB::table('sections')->where('program_id',$id)->exists()))AcademicService::fail('Programs with student or section history must stay on file.');
   $row=DB::table($resource)->where('id',$id)->lockForUpdate()->first();abort_unless($row,404);if($resource!=='rooms')$this->assertMutable($resource,$row);DB::table($resource)->where('id',$id)->update(['active'=>false,'updated_at'=>now()]);$service->audit('deactivated',$resource,$id,(array)$row);},3);
  return request()->expectsJson()?response()->json(['message'=>'Record deactivated. History was retained.']):back()->with('status','Record deactivated. History was retained.');
 }
 public function activate(string $resource,int $id,AcademicService $service){
  abort_unless(is_array(config('academic.'.$resource)),404);
  DB::transaction(function()use($resource,$id,$service){DB::table('periods')->orderBy('id')->lockForUpdate()->get();$row=DB::table($resource)->where('id',$id)->lockForUpdate()->first();abort_unless($row,404);
   $this->workflowGuard($resource,$row);
   if(in_array($resource,['schedules','offerings','curriculum_subjects']))$this->assertMutable($resource,$row);
   if($resource==='curriculum_subjects' && $this->curriculumUsed($row->curriculum_id))AcademicService::fail('The target curriculum has historical assignments. Create a new version.');
   if($resource==='prerequisites'){DB::table('subjects')->orderBy('id')->lockForUpdate()->get();$service->prerequisite((array)$row,$id);}
   if($resource==='schedules')$service->schedule((array)$row,$id);
   DB::table($resource)->where('id',$id)->update(['active'=>true,'updated_at'=>now()]);$service->audit('activated',$resource,$id);
  },3);return request()->expectsJson()?response()->json(['message'=>'Record activated.']):back()->with('status','Record activated.');
 }
 private function curriculumUsed(int $id):bool {
  return DB::table('students')->where('curriculum_id',$id)->exists() || DB::table('curriculum_assignments')->where('previous_curriculum_id',$id)->orWhere('curriculum_id',$id)->exists();
 }
 private function prerequisiteInput(Request $request,string $resource):?array {
  if($resource!=='subjects' || !$request->boolean('manage_prerequisites'))return null;
  $data=$request->validate(['prerequisite_ids'=>'array|max:1000','prerequisite_ids.*'=>'integer|distinct|exists:subjects,id']);
  return $data['prerequisite_ids']??[];
 }
 private function syncPrerequisites(int $subjectId,array $ids,AcademicService $service):void {
  DB::table('subjects')->orderBy('id')->lockForUpdate()->get();
  $before=DB::table('prerequisites')->where('subject_id',$subjectId)->get()->keyBy('required_subject_id');
  DB::table('prerequisites')->where('subject_id',$subjectId)->update(['active'=>false,'updated_at'=>now()]);
  foreach($ids as $required){
   $existing=$before[$required]??null;
   if(!DB::table('subjects')->where(['id'=>$required,'active'=>true])->exists())AcademicService::fail('Choose active prerequisite subjects.');
   $edge=['subject_id'=>$subjectId,'required_subject_id'=>$required,'alternative_group'=>$existing?->alternative_group??0,'minimum_grade'=>$existing?->minimum_grade];
   $service->prerequisite($edge,$existing?->id);
   DB::table('prerequisites')->updateOrInsert(['subject_id'=>$subjectId,'required_subject_id'=>$required],$edge+['active'=>true,'created_at'=>$existing?->created_at??now(),'updated_at'=>now()]);
  }
  $service->audit('prerequisites_updated','subjects',$subjectId,['required_subject_ids'=>$ids]);
 }
 private function assertMutable(string $resource,object $row):void {
  $this->workflowGuard($resource,$row);
  if(in_array($resource,['subjects','rooms','professor_subjects','professor_availabilities'])){
   $locked=DB::table('offerings as o')->join('sections as sec','sec.id','=','o.section_id')->join('schedule_locks as l',fn($j)=>$j->on('l.period_id','=','o.period_id')->on('l.year_level','=','sec.year_level'));
   if($resource==='subjects')$locked->where('o.subject_id',$row->id);else{$locked->join('schedules as meeting','meeting.offering_id','=','o.id')->where('meeting.active',true);if($resource==='rooms')$locked->where('meeting.room_id',$row->id);else $locked->where('meeting.professor_id',$row->professor_id);}
   if($locked->exists())AcademicService::fail('This record supports a finalized year-level schedule and cannot be changed.');
  }
  if(in_array($resource,['curriculums','curriculum_subjects']) && $this->curriculumUsed($resource==='curriculums'?$row->id:$row->curriculum_id))AcademicService::fail('This curriculum is assigned to students. Create a new version to preserve historical requirements.');
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
 private function workflowGuard(string $resource,object $row):void {
  if($resource==='schedules')$row=DB::table('offerings')->find($row->offering_id);
  if(in_array($resource,['schedules','offerings','sections'])){ $year=$resource==='sections'?$row->year_level:DB::table('sections')->where('id',$row->section_id)->value('year_level');app(\App\Services\TermWorkflowService::class)->mutable($row->period_id,$year); }
  if($resource==='periods' && isset($row->id) && ($row->closed_at || $row->is_current || DB::table('schedule_locks')->where('period_id',$row->id)->exists()))AcademicService::fail('Use the academic calendar to manage this semester.');
 }
}
