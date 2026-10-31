<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class ImportService {
public function run(string $source,array $payload,?int $curriculumId=null):array {
$service=app(AcademicService::class);
  $key=$source==='admission'?'students':'employees';
  if(($payload['source']??null)!==$source || ($payload['schema_version']??null)!=='1.0' || !isset($payload[$key]) || !is_array($payload[$key]) || !array_is_list($payload[$key]) || count($payload[$key])>1000)AcademicService::fail('Use a version 1.0 JSON envelope with the matching source and a list of up to 1,000 records.');
  if($source==='admission' && !$curriculumId)AcademicService::fail('Select the curriculum for this Admission import.');
  $created=0;$updated=0;$errors=[];
  foreach($payload[$key] as $index=>$record){
   try{$existed=DB::transaction(function()use($record,$source,$curriculumId){
    $base=['first_name'=>'required|string|max:100','last_name'=>'required|string|max:100','middle_name'=>'nullable|string|max:100','email'=>'required|email|max:255'];
    $rules=$source==='admission'?['admission_id'=>'required|string|max:100','admission_status'=>'required|in:ADMITTED,APPROVED','program_code'=>'required|string|max:30','year_level'=>'required|integer|min:1|max:8','temporary_password'=>'nullable|string|min:12|max:200']:['employee_id'=>'required|string|max:100','employment_type'=>'required|in:FULL_TIME,PART_TIME','employment_status'=>'required|in:ACTIVE,INACTIVE','department_code'=>'required|string|max:50'];
    if(!is_array($record))AcademicService::fail('Record must be an object.');$r=Validator::make($record,$base+$rules)->validate();
    $name=implode(' ',array_filter([$r['first_name'],$r['middle_name']??null,$r['last_name']]));
    if($source==='admission'){
     $curriculum=DB::table('curriculums as c')->join('programs as p','p.id','=','c.program_id')->where('c.id',$curriculumId)->where('c.active',true)->select('c.id','p.code')->first();if(!$curriculum || $curriculum->code!==$r['program_code'])AcademicService::fail('The program does not match the selected active curriculum.');
     $student=DB::table('students')->where('admission_id',$r['admission_id'])->first();
     if($student){$user=User::findOrFail($student->user_id);$user->update(['name'=>$name,'email'=>$r['email']]);return true;}
     $temporary=$r['temporary_password']??\Illuminate\Support\Str::random(48);
     $user=User::create(['name'=>$name,'email'=>$r['email'],'password'=>Hash::make($temporary)]);
     $studentId=DB::table('students')->insertGetId(['user_id'=>$user->id,'admission_id'=>$r['admission_id'],'curriculum_id'=>$curriculum->id,'year_level'=>$r['year_level'],'classification'=>'regular','admission_year'=>preg_match('/ADM-(\d{4})/',$r['admission_id'],$cohort)?(int)$cohort[1]:null,'created_at'=>now(),'updated_at'=>now()]);if($current=app(TermWorkflowService::class)->current())app(TermWorkflowService::class)->assign($studentId,$current->id);if(empty($r['temporary_password'])){$delivery=\Illuminate\Support\Facades\Password::sendResetLink(['email'=>$user->email]);if($delivery!==\Illuminate\Support\Facades\Password::RESET_LINK_SENT)AcademicService::fail('Account invitation could not be sent.');}return false;
    }
    $exists=DB::table('professors')->where('employee_id',$r['employee_id'])->exists();
    DB::table('professors')->updateOrInsert(['employee_id'=>$r['employee_id']],['name'=>$name,'email'=>$r['email'],'department'=>$r['department_code'],'employment_type'=>$r['employment_type'],'active'=>$r['employment_status']==='ACTIVE','last_synced_at'=>now(),'updated_at'=>now()]);
    if(array_key_exists('teaching_assignments',$record)){
     $assignments=Validator::make($record,['teaching_assignments'=>'present|array|max:1000','teaching_assignments.*.subject_code'=>'required|string|max:30'])->validate()['teaching_assignments'];
     $professorId=DB::table('professors')->where('employee_id',$r['employee_id'])->value('id');$subjectIds=[];
     foreach($assignments as $assignment){$subjectId=DB::table('subjects')->where(['code'=>$assignment['subject_code'],'active'=>true])->value('id');if(!$subjectId)AcademicService::fail('An authorized teaching subject code does not match an active local subject.');$subjectIds[]=$subjectId;}
     DB::table('periods')->orderBy('id')->lockForUpdate()->get();
     DB::table('professor_subjects')->where('professor_id',$professorId)->update(['active'=>false,'updated_at'=>now()]);
     foreach(array_unique($subjectIds) as $subjectId)DB::table('professor_subjects')->updateOrInsert(['professor_id'=>$professorId,'subject_id'=>$subjectId],['active'=>true,'updated_at'=>now()]);
    }
    return $exists;
   });if($existed)$updated++;else $created++;
   }catch(\Throwable $e){
    // Never store exception payloads: they can contain credentials or private student data.
    $errors[]=['row'=>$index+1,'error'=>$e instanceof \Illuminate\Validation\ValidationException?implode(' ',array_keys($e->errors())).' validation failed.':'Record could not be imported; verify identifiers and unique email.'];
   }
  }
  $id=DB::table('external_sync_logs')->insertGetId(['source'=>$source,'received'=>count($payload[$key]),'created'=>$created,'updated'=>$updated,'rejected'=>count($errors),'errors'=>json_encode($errors),'created_at'=>now(),'updated_at'=>now()]);$service->audit('imported','external_sync_logs',$id,['source'=>$source,'created'=>$created,'updated'=>$updated,'rejected'=>count($errors)]);

return ['log_id'=>$id,'received'=>count($payload[$key]),'created'=>$created,'updated'=>$updated,'rejected'=>count($errors),'errors'=>$errors];
}
}
