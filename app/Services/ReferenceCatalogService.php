<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class ReferenceCatalogService {
 public function import():array {
  $reference=json_decode(file_get_contents(resource_path('data/plp-reference-2022.json')),true,512,JSON_THROW_ON_ERROR);
  return DB::transaction(function()use($reference){
   DB::table('periods')->orderBy('id')->lockForUpdate()->get();
   $programs=[];$curriculums=[];$subjects=[];
   $titles=[];foreach($reference['curriculum']['subjects'] as $entry)$titles[$entry['code']][]=$entry['title'];
   foreach($reference['programs'] as $program){
    DB::table('programs')->insertOrIgnore(['code'=>$program['code'],'name'=>$program['name'],'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
    $programs[$program['name']]=DB::table('programs')->where('code',$program['code'])->value('id');
    DB::table('colleges')->insertOrIgnore(['name'=>$program['college'],'active'=>true,'created_at'=>now(),'updated_at'=>now()]);DB::table('programs')->where('id',$programs[$program['name']])->whereNull('college_id')->update(['college_id'=>DB::table('colleges')->where('name',$program['college'])->value('id')]);
   }
   foreach($reference['curriculum']['sources'] as $source){
    $programId=$programs[$source['program']];$name='Supplied 2022–2023 curriculum';
    $existing=DB::table('curriculums')->where(['program_id'=>$programId,'name'=>$name])->first();
    if($existing){$curriculums[$source['program']]=$existing->id;continue;}
    $curriculums[$source['program']]=DB::table('curriculums')->insertGetId(['program_id'=>$programId,'name'=>$name,'academic_year'=>'2022-2023','active'=>true,'created_at'=>now(),'updated_at'=>now()]);
   }
   foreach($reference['curriculum']['subjects'] as $entry){
    $subject=DB::table('subjects')->where('code',$entry['code'])->first();
    if($subject && (int)$subject->units!==(int)$entry['units'])AcademicService::fail('Subject '.$entry['code'].' has different existing units. Review the catalog before importing.');
    if($subject && !in_array($subject->name,$titles[$entry['code']],true))AcademicService::fail('Subject '.$entry['code'].' has an unrelated existing title. Review it before importing.');
    $id=$subject?->id??DB::table('subjects')->insertGetId(['code'=>$entry['code'],'name'=>$entry['title'],'units'=>$entry['units'],'type'=>str_starts_with($entry['code'],'PE ')?'physical_education':'lecture','allowed_days'=>'Mon,Tue,Wed,Thu,Fri,Sat,Sun','active'=>true,'created_at'=>now(),'updated_at'=>now()]);
    $subjects[$entry['code']]=$id;
    DB::table('curriculum_subjects')->insertOrIgnore(['curriculum_id'=>$curriculums[$entry['program']],'subject_id'=>$id,'year_level'=>$entry['yearLevel'],'semester'=>$entry['semester'],'elective'=>false,'source_title'=>$entry['title'],'minimum_year'=>$entry['minimumYear'],'minimum_passed_units'=>$entry['minimumPassedUnits'],'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
   }
   foreach($reference['curriculum']['subjects'] as $entry){
    foreach(array_filter(array_map('trim',explode(',',$entry['prerequisites']))) as $code){
     if(!isset($subjects[$code]))AcademicService::fail('Unresolved prerequisite in the supplied catalog: '.$code);
     $edge=['subject_id'=>$subjects[$entry['code']],'required_subject_id'=>$subjects[$code],'alternative_group'=>0];
     if(!DB::table('prerequisites')->where(['subject_id'=>$edge['subject_id'],'required_subject_id'=>$edge['required_subject_id']])->exists()){
      if(DB::table('proposal_items as i')->join('offerings as o','o.id','=','i.offering_id')->where('o.subject_id',$edge['subject_id'])->exists())AcademicService::fail('A supplied prerequisite would change a subject with proposal history. Create a separately reviewed version.');
      app(AcademicService::class)->prerequisite($edge);
      DB::table('prerequisites')->insert($edge+['active'=>true,'created_at'=>now(),'updated_at'=>now()]);
     }
    }
   }
   app(AcademicService::class)->audit('reference_catalog_imported','curriculums',null,['source'=>'Supplied 2022–2023 BSIT/BSCS','curriculum_ids'=>array_values($curriculums)]);
   return ['curriculums'=>count($curriculums),'subjects'=>count($subjects)];
  });
 }
}
