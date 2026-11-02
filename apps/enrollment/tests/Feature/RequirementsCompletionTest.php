<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\{AcademicService,EnrollmentService,ReferenceCatalogService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class RequirementsCompletionTest extends TestCase {
 use RefreshDatabase,\Tests\Support\AcademicFixture;
 public function test_settings_are_registrar_only_validated_and_audited():void {
  $f=$this->fixture();$values=['institution_name'=>'Test institution','grade_order'=>'lower','default_section_capacity'=>35];
  $this->actingAs($f['user'])->post('/settings',$values)->assertForbidden();
  $this->actingAs($f['registrar'])->post('/settings',$values)->assertSessionHasNoErrors();
  $this->assertDatabaseHas('system_settings',['key'=>'grade_order','value'=>'lower']);
  $this->assertDatabaseHas('audit_logs',['action'=>'settings_updated']);
  $this->post('/settings',array_replace($values,['grade_order'=>'guessed']))->assertSessionHasErrors('grade_order');
  $this->get('/settings')->assertOk()->assertSee('Test institution');
 }
 public function test_professor_directory_exposes_qualifications_availability_and_filters():void {
  $f=$this->fixture();$this->row('professors',['employee_id'=>'OTHER','name'=>'Other teacher','department'=>'Other','employment_type'=>'PART_TIME']);
  $this->actingAs($f['registrar'])->get('/professors?search=Professor&department=CCS&active=1&sort=name&direction=desc')->assertOk()->assertSee('IT001')->assertSee('07:00')->assertSee('Not recorded')->assertDontSee('Other teacher');
  $this->actingAs($f['user'])->get('/professors')->assertForbidden();
 }
 private function subjectData(string $code,string $name,array $ids):array{return ['code'=>$code,'name'=>$name,'units'=>3,'type'=>'lecture','allowed_days'=>'Mon,Tue','manage_prerequisites'=>1,'prerequisite_ids'=>$ids];}
 public function test_subject_editor_saves_and_removes_prerequisites_atomically():void {
  $f=$this->fixture();$this->actingAs($f['registrar']);
  $this->post('/academic/subjects',$this->subjectData('IT002','Next subject',[$f['subject']]))->assertSessionHasNoErrors();
  $id=DB::table('subjects')->where('code','IT002')->value('id');
  $this->assertDatabaseHas('prerequisites',['subject_id'=>$id,'required_subject_id'=>$f['subject'],'active'=>1]);
  $this->post('/academic/subjects/'.$f['subject'],$this->subjectData('IT001','Changed title',[$id]))->assertSessionHasErrors('academic');
  $this->assertDatabaseHas('subjects',['id'=>$f['subject'],'name'=>'Introduction to IT']);
  $this->post('/academic/subjects/'.$id,$this->subjectData('IT002','Next subject',[]))->assertSessionHasNoErrors();
  $this->assertDatabaseHas('prerequisites',['subject_id'=>$id,'active'=>0]);
 }
 public function test_schedule_preview_checks_conflicts_without_writing_and_save_revalidates():void {
  $f=$this->fixture();$this->actingAs($f['registrar']);
  $data=['offering_id'=>$f['offering'],'professor_id'=>$f['professor'],'room_id'=>$f['room'],'day'=>'Mon','start_time'=>'10:00','end_time'=>'11:00'];
  $this->postJson('/scheduler/preview',$data)->assertOk();$this->assertDatabaseCount('schedules',1);
  $this->postJson('/scheduler/preview',array_replace($data,['start_time'=>'08:30','end_time'=>'09:30']))->assertUnprocessable();
  $this->row('room_closures',['room_id'=>$f['room'],'period_id'=>$f['period'],'day'=>'Mon','start_time'=>'10:00','end_time'=>'11:00','reason'=>'Maintenance']);
  $this->postJson('/academic/schedules',$data)->assertUnprocessable();$this->assertDatabaseCount('schedules',1);
  $this->actingAs($f['user'])->postJson('/scheduler/preview',$data)->assertForbidden();
 }
 public function test_reference_catalog_import_is_repeatable_preserves_students_and_units():void {
  $f=$this->fixture();$this->actingAs($f['registrar']);$service=app(ReferenceCatalogService::class);$service->import();$count=DB::table('subjects')->count();$service->import();$this->assertSame($count,DB::table('subjects')->count());
  $this->assertDatabaseHas('students',['id'=>$f['student'],'curriculum_id'=>$f['curriculum']]);
  foreach(['BSIT'=>49,'BSCS'=>51] as $code=>$expected){$curriculum=DB::table('curriculums as c')->join('programs as p','p.id','=','c.program_id')->where('p.code',$code)->where('c.academic_year','2022-2023')->value('c.id');$rows=DB::table('curriculum_subjects as c')->join('subjects as s','s.id','=','c.subject_id')->where('c.curriculum_id',$curriculum);$this->assertSame($expected,(clone $rows)->count());$this->assertSame(146,(int)$rows->sum('s.units'));}
 }
 public function test_reference_conflict_rolls_back_instead_of_reusing_an_unrelated_subject():void {
  $f=$this->fixture();$this->row('subjects',['code'=>'COMP 101','name'=>'Unrelated legacy class','units'=>3]);
  $this->actingAs($f['registrar'])->post('/imports/reference')->assertSessionHasErrors('academic');
  $this->assertSame(1,DB::table('curriculums')->count());$this->assertDatabaseHas('subjects',['code'=>'COMP 101','name'=>'Unrelated legacy class']);
 }
 public function test_new_cor_snapshots_explicit_period_fields_and_seal_without_changing_history():void {
  $f=$this->fixture();DB::table('periods')->where('id',$f['period'])->update(['academic_year'=>'2026-2027','semester'=>1]);
  $service=app(EnrollmentService::class);$this->actingAs($f['registrar']);$id=$service->propose($f['student'],$f['period'],[$f['offering']]);$this->actingAs($f['user']);$service->decide($id,1,true,null);$this->actingAs($f['registrar']);$service->finalize($id);
  $original=DB::table('cor_versions')->value('snapshot');$snapshot=json_decode($original);$this->assertSame('2026-2027',$snapshot->academic_year);$this->assertSame(1,$snapshot->semester);$this->assertStringStartsWith('data:image/png;base64,',$snapshot->logo);
  $this->get('/enrollments/'.$id.'/cor')->assertOk()->assertSee('Academic year: 2026-2027')->assertSee('Semester: 1');
  $this->get('/enrollments/'.$id.'/pdf')->assertOk()->assertHeader('Content-Type','application/pdf');
  $this->assertSame($original,DB::table('cor_versions')->value('snapshot'));
 }
 public function test_table_sort_is_allowlisted_and_filters_apply_before_pagination():void {
  $f=$this->fixture();$this->actingAs($f['registrar'])->get('/academic/subjects?sort=name&direction=desc&active=1')->assertOk()->assertSee('aria-sort="descending"',false);
  $this->get('/records?sort=invalid%20sql&classification=regular')->assertOk()->assertSee('ADM-1');
  $this->get('/academic/sections')->assertOk();
  $this->getJson('/enrollments?search[]=invalid')->assertUnprocessable();
  $this->getJson('/records?search[]=invalid')->assertUnprocessable();
  $this->getJson('/academic/subjects?search[]=invalid')->assertUnprocessable();
 }
 public function test_year_standing_uses_completed_curriculum_units():void {
  $f=$this->fixture();DB::table('curriculum_subjects')->where('subject_id',$f['subject'])->update(['minimum_year'=>2,'minimum_passed_units'=>3]);
  try{app(AcademicService::class)->eligible($f['student'],$f['subject']);$this->fail('Year standing was not enforced.');}catch(\Illuminate\Validation\ValidationException $e){$this->assertStringContainsString('year standing',$e->getMessage());}
  DB::table('students')->where('id',$f['student'])->update(['year_level'=>2,'classification'=>'irregular']);
  $this->expectException(\Illuminate\Validation\ValidationException::class);app(AcademicService::class)->eligible($f['student'],$f['subject']);
 }
}
