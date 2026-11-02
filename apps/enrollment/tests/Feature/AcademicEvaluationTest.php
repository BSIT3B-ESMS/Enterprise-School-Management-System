<?php
namespace Tests\Feature;
use Tests\TestCase;
use Tests\Support\AcademicFixture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\EnrollmentService;
use Laravel\Sanctum\Sanctum;
class AcademicEvaluationTest extends TestCase {
 use RefreshDatabase,AcademicFixture;
 private array $f; private User $head;
 protected function setUp():void{parent::setUp();$this->f=$this->fixture();$this->head=User::factory()->create(['role'=>'department_head','must_change_password'=>false]);$this->row('department_head_programs',['user_id'=>$this->head->id,'program_id'=>$this->f['program']]);DB::table('students')->where('id',$this->f['student'])->update(['classification'=>'transferee']);}
 private function requestCase():int{$this->actingAs($this->f['user'])->post('/special-cases',['period_id'=>$this->f['period'],'request'=>'Evaluate my prior official academic records.','requested_subject_ids'=>[$this->f['subject']]])->assertSessionHasNoErrors();return DB::table('special_cases')->max('id');}
 private function credit():array{return ['subject_id'=>$this->f['subject'],'source_institution'=>'Example College','source_subject'=>'INTRO101 Introduction','source_grade'=>'1.75','source_units'=>'3','evidence_reference'=>'TEST-TRANSCRIPT-001','reason'=>'Official transcript and syllabus meet equivalent subject outcomes.','decision'=>'approved'];}
 private function approval(array $credits=[]):array{return ['status'=>'approved','evaluation'=>'Records verified against official curriculum and credit policy.','credits'=>$credits];}
 private function newCurriculum():int{$id=$this->row('curriculums',['program_id'=>$this->f['program'],'name'=>'New version']);$this->row('curriculum_subjects',['curriculum_id'=>$id,'subject_id'=>$this->f['subject'],'year_level'=>1,'semester'=>1]);return $id;}
 public function test_transferee_credit_has_evidence_and_becomes_visible_without_enrollment():void{
  $id=$this->requestCase();$this->actingAs($this->head)->get('/special-cases/'.$id)->assertOk()->assertSee('Add subject equivalency');
  $this->post('/special-cases/'.$id.'/evaluate',$this->approval([$this->credit()]))->assertSessionHasNoErrors();
  $this->assertDatabaseHas('academic_records',['student_id'=>$this->f['student'],'subject_id'=>$this->f['subject'],'status'=>'CREDITED']);$this->assertDatabaseCount('enrollments',0);$this->assertDatabaseCount('cor_versions',0);
  $this->actingAs($this->f['user'])->get('/records/'.$this->f['student'])->assertOk()->assertSee('Example College')->assertSee('Completed / credited');
  $this->get('/special-cases/'.$id)->assertOk()->assertSee('TEST-TRANSCRIPT-001');
 }
 public function test_rejected_equivalency_does_not_award_credit_and_cannot_be_reapproved():void{
  $id=$this->requestCase();$credit=$this->credit();$credit['decision']='rejected';
  $this->actingAs($this->head)->post('/special-cases/'.$id.'/evaluate',$this->approval([$credit]))->assertSessionHasNoErrors();$this->assertDatabaseCount('academic_records',0);$this->assertDatabaseHas('credit_evaluations',['decision'=>'rejected']);
  $this->post('/special-cases/'.$id.'/evaluate',$this->approval([$this->credit()]))->assertSessionHasErrors('academic');$this->assertDatabaseCount('academic_records',0);
 }
 public function test_rejected_case_cannot_award_credits_and_rolls_back():void{
  $id=$this->requestCase();$data=$this->approval([$this->credit()]);$data['status']='rejected';
  $this->actingAs($this->head)->post('/special-cases/'.$id.'/evaluate',$data)->assertSessionHasErrors('academic');$this->assertDatabaseCount('credit_evaluations',0);$this->assertDatabaseHas('special_cases',['id'=>$id,'status'=>'pending']);
 }
 public function test_missing_evidence_duplicate_subjects_and_outside_curriculum_are_rejected():void{
  $id=$this->requestCase();$credit=$this->credit();unset($credit['evidence_reference']);
  $this->actingAs($this->head)->postJson('/special-cases/'.$id.'/evaluate',$this->approval([$credit]))->assertUnprocessable()->assertJsonValidationErrors('credits.0.evidence_reference');
  $this->postJson('/special-cases/'.$id.'/evaluate',$this->approval([$this->credit(),$this->credit()]))->assertUnprocessable();
  $credit=$this->credit();$credit['subject_id']=$this->row('subjects',['code'=>'OUT','name'=>'Outside curriculum','units'=>3]);
  $this->postJson('/special-cases/'.$id.'/evaluate',$this->approval([$credit]))->assertUnprocessable()->assertJsonValidationErrors('academic');$this->assertDatabaseCount('academic_records',0);
 }
 public function test_evaluation_permissions_and_duplicate_request():void{
  $id=$this->requestCase();$this->post('/special-cases',['period_id'=>$this->f['period'],'request'=>'Duplicate request'])->assertSessionHasErrors('academic');
  $this->post('/special-cases/'.$id.'/evaluate',$this->approval())->assertForbidden();
  $other=User::factory()->create(['role'=>'department_head','must_change_password'=>false]);$this->actingAs($other)->get('/special-cases/'.$id)->assertForbidden();$this->post('/special-cases/'.$id.'/evaluate',$this->approval())->assertForbidden();
 }
 public function test_credit_cannot_duplicate_completed_subject():void{
  $id=$this->requestCase();$this->row('academic_records',['student_id'=>$this->f['student'],'subject_id'=>$this->f['subject'],'period_id'=>$this->f['period'],'status'=>'PASSED']);
  $this->actingAs($this->head)->post('/special-cases/'.$id.'/evaluate',$this->approval([$this->credit()]))->assertSessionHasErrors('academic');$this->assertDatabaseCount('academic_records',1);$this->assertDatabaseCount('credit_evaluations',0);
 }
 public function test_curriculum_reassignment_preserves_history_and_requires_fresh_evaluation():void{
  $case=$this->requestCase();$this->actingAs($this->head)->post('/special-cases/'.$case.'/evaluate',$this->approval())->assertSessionHasNoErrors();
  $new=$this->newCurriculum();$this->actingAs($this->f['registrar'])->post('/records/'.$this->f['student'].'/curriculum',['previous_curriculum_id'=>$this->f['curriculum'],'curriculum_id'=>$new,'reason'=>'Returning student record verified against revised program requirements.'])->assertSessionHasNoErrors();
  $this->assertDatabaseHas('students',['id'=>$this->f['student'],'curriculum_id'=>$new]);$this->assertDatabaseHas('curriculum_assignments',['previous_curriculum_id'=>$this->f['curriculum'],'curriculum_id'=>$new]);
  $oldSubject=DB::table('curriculum_subjects')->where('curriculum_id',$this->f['curriculum'])->first();$this->post('/academic/curriculum_subjects/'.$oldSubject->id.'/deactivate')->assertSessionHasErrors('academic');
  $this->actingAs($this->head)->post('/enrollments',['student_id'=>$this->f['student'],'period_id'=>$this->f['period'],'offering_ids'=>[$this->f['offering']]])->assertSessionHasErrors('academic');
  $this->actingAs($this->f['user'])->get('/records/'.$this->f['student'])->assertOk()->assertSee('Assignment history')->assertSee('New version');
 }
 public function test_open_proposal_blocks_curriculum_and_classification_change():void{
  $id=$this->requestCase();$this->actingAs($this->head)->post('/special-cases/'.$id.'/evaluate',$this->approval());app(EnrollmentService::class)->propose($this->f['student'],$this->f['period'],[$this->f['offering']],true);
  $new=$this->newCurriculum();$this->actingAs($this->f['registrar'])->post('/records/'.$this->f['student'].'/curriculum',['previous_curriculum_id'=>$this->f['curriculum'],'curriculum_id'=>$new,'reason'=>'Attempt curriculum reassignment during active enrollment.'])->assertSessionHasErrors('academic');
  $this->post('/records/'.$this->f['student'],['classification'=>'regular','year_level'=>1])->assertSessionHasErrors('academic');$this->assertDatabaseCount('curriculum_assignments',0);
 }
 public function test_forwarded_special_draft_needs_one_final_student_acceptance():void{
  $case=$this->requestCase();$this->actingAs($this->head)->post('/special-cases/'.$case.'/evaluate',$this->approval());
  $this->post('/special-cases/'.$case.'/forward-to-registrar',['action'=>'forward','offering_ids'=>[$this->f['offering']]])->assertSessionHasNoErrors();
  $id=DB::table('enrollments')->value('id');$this->assertDatabaseHas('enrollments',['id'=>$id,'status'=>'special_ready']);$this->assertDatabaseCount('cor_versions',0);
  $this->actingAs($this->f['user'])->post('/enrollments/'.$id.'/decision',['version'=>1,'decision'=>'accept'])->assertSessionHasErrors('academic');
  $this->actingAs($this->f['registrar'])->post('/enrollments',['student_id'=>$this->f['student'],'period_id'=>$this->f['period'],'offering_ids'=>[$this->f['offering']]])->assertSessionHasNoErrors();
  $this->actingAs($this->f['user'])->post('/enrollments/'.$id.'/decision',['version'=>2,'decision'=>'accept'])->assertSessionHasNoErrors();$this->assertDatabaseHas('enrollments',['id'=>$id,'status'=>'finalized']);$this->assertDatabaseCount('cor_versions',1);
 }
 public function test_offering_specific_days_are_enforced():void{
  DB::table('offerings')->where('id',$this->f['offering'])->update(['allowed_days'=>'Tue']);
  $this->actingAs($this->f['registrar'])->post('/academic/schedules',['offering_id'=>$this->f['offering'],'professor_id'=>$this->f['professor'],'room_id'=>$this->f['room'],'day'=>'Mon','start_time'=>'10:00','end_time'=>'11:00'])->assertSessionHasErrors('academic');
 }
 public function test_proposed_api_aliases_update_deactivate_and_report_availability():void{
  Sanctum::actingAs($this->f['registrar'],['portal']);
  $this->putJson('/api/v1/subjects/'.$this->f['subject'],['code'=>'IT001','name'=>'Edited subject','units'=>3,'type'=>'lecture','allowed_days'=>'Mon,Tue'])->assertOk();
  $this->getJson('/api/v1/rooms/'.$this->f['room'].'/availability?period_id='.$this->f['period'])->assertOk()->assertJsonPath('scheduled_meetings.0.offering_id',$this->f['offering']);
  $this->getJson('/api/v1/professors/'.$this->f['professor'].'/availability?period_id='.$this->f['period'])->assertOk()->assertJsonCount(1,'available_windows');
  $this->getJson('/api/v1/rooms/'.$this->f['room'].'/availability')->assertUnprocessable();
  $this->deleteJson('/api/v1/subjects/'.$this->f['subject'])->assertOk();$this->assertDatabaseHas('subjects',['id'=>$this->f['subject'],'active'=>false]);
 }
 public function test_credit_batch_rolls_back_if_a_later_row_is_invalid():void{
  $id=$this->requestCase();$bad=$this->credit();$bad['subject_id']=$this->row('subjects',['code'=>'BAD-CREDIT','name'=>'Outside curriculum','units'=>3]);
  $this->actingAs($this->head)->post('/special-cases/'.$id.'/evaluate',$this->approval([$this->credit(),$bad]))->assertSessionHasErrors('academic');$this->assertDatabaseCount('academic_records',0);$this->assertDatabaseCount('credit_evaluations',0);
 }
 public function test_returning_student_reassignment_keeps_finalized_cor_and_old_requirements():void{
  DB::table('students')->where('id',$this->f['student'])->update(['classification'=>'regular']);$svc=app(EnrollmentService::class);$this->actingAs($this->f['registrar']);$id=$svc->propose($this->f['student'],$this->f['period'],[$this->f['offering']]);$this->actingAs($this->f['user']);$svc->decide($id,1,true,null);$this->actingAs($this->f['registrar']);$svc->finalize($id);
  $snapshot=DB::table('cor_versions')->value('snapshot');$new=$this->newCurriculum();
  $this->post('/records/'.$this->f['student'],['classification'=>'returning','year_level'=>2])->assertSessionHasNoErrors();
  $this->post('/records/'.$this->f['student'].'/curriculum',['previous_curriculum_id'=>$this->f['curriculum'],'curriculum_id'=>$new,'reason'=>'Returning student approved for revised requirements after verification.'])->assertSessionHasNoErrors();
  $this->assertSame($snapshot,DB::table('cor_versions')->value('snapshot'));$this->get('/enrollments/'.$id.'/pdf')->assertOk();$this->get('/curriculums/'.$this->f['curriculum'])->assertOk()->assertSee('Total active curriculum units:');
  $this->post('/records/'.$this->f['student'].'/curriculum',['previous_curriculum_id'=>$this->f['curriculum'],'curriculum_id'=>$new,'reason'=>'Stale assignment request must not create duplicate history.'])->assertSessionHasErrors('academic');$this->assertDatabaseCount('curriculum_assignments',1);
 }
 public function test_registrar_cannot_drop_the_accepted_special_class():void{
  $case=$this->requestCase();$this->actingAs($this->head)->post('/special-cases/'.$case.'/evaluate',$this->approval());$svc=app(EnrollmentService::class);$id=$svc->propose($this->f['student'],$this->f['period'],[$this->f['offering']],true);$this->actingAs($this->f['user']);$svc->decide($id,1,true,null);
  $other=$this->row('subjects',['code'=>'OTHER-FINAL','name'=>'Other subject','units'=>3]);$this->row('curriculum_subjects',['curriculum_id'=>$this->f['curriculum'],'subject_id'=>$other,'year_level'=>1,'semester'=>1]);$offering=$this->row('offerings',['subject_id'=>$other,'section_id'=>$this->f['section'],'period_id'=>$this->f['period'],'capacity'=>2]);
  $this->actingAs($this->f['registrar'])->post('/enrollments',['student_id'=>$this->f['student'],'period_id'=>$this->f['period'],'offering_ids'=>[$offering]])->assertSessionHasErrors('academic');$this->assertDatabaseHas('enrollments',['id'=>$id,'status'=>'finalized','version'=>1]);
 }
 public function test_api_evaluation_approve_alias_and_privacy():void{
  $id=$this->requestCase();Sanctum::actingAs($this->head,['portal']);$this->postJson('/api/v1/special-cases/'.$id.'/approve',['evaluation'=>'Verified official records; no equivalency credits requested.'])->assertOk();$this->getJson('/api/v1/special-cases/'.$id)->assertOk()->assertJsonPath('case.status','approved');
  $other=User::factory()->create(['role'=>'student','must_change_password'=>false]);Sanctum::actingAs($other,['portal']);$this->getJson('/api/v1/special-cases/'.$id)->assertForbidden();$this->getJson('/api/v1/rooms/'.$this->f['room'].'/availability?period_id='.$this->f['period'])->assertForbidden();
 }
 public function test_prerequisite_api_creates_and_reads_validated_relationships():void{
  $prereq=$this->row('subjects',['code'=>'API-PRE','name'=>'API prerequisite','units'=>3]);Sanctum::actingAs($this->f['registrar'],['portal']);
  $this->postJson('/api/v1/subjects/'.$this->f['subject'].'/prerequisites',['required_subject_id'=>$prereq,'alternative_group'=>0])->assertCreated();
  $this->getJson('/api/v1/subjects/'.$this->f['subject'].'/prerequisites')->assertOk()->assertJsonPath('0.code','API-PRE');
  $this->postJson('/api/v1/subjects/'.$prereq.'/prerequisites',['required_subject_id'=>$this->f['subject'],'alternative_group'=>0])->assertUnprocessable();
 }
 public function test_department_head_cannot_prepare_regular_student_proposal():void{
  DB::table('students')->where('id',$this->f['student'])->update(['classification'=>'regular']);$this->actingAs($this->head)->post('/enrollments',['student_id'=>$this->f['student'],'period_id'=>$this->f['period'],'offering_ids'=>[$this->f['offering']]])->assertSessionHasErrors('academic');$this->assertDatabaseCount('enrollments',0);
 }
 public function test_temporary_password_registrar_cannot_import_via_api():void{
  $this->f['registrar']->forceFill(['must_change_password'=>true])->save();Sanctum::actingAs($this->f['registrar'],['portal']);$this->postJson('/api/v1/integrations/ems',['source'=>'ems','schema_version'=>'1.0','employees'=>[]])->assertForbidden();$this->assertDatabaseCount('external_sync_logs',0);
 }
 public function test_role_dashboards_show_scoped_details_and_finalized_student_schedule():void{
  DB::table('students')->where('id',$this->f['student'])->update(['classification'=>'regular']);$svc=app(EnrollmentService::class);$this->actingAs($this->f['registrar']);$id=$svc->propose($this->f['student'],$this->f['period'],[$this->f['offering']]);$this->actingAs($this->f['user']);$svc->decide($id,1,true,null);$this->actingAs($this->f['registrar']);$svc->finalize($id);
  $this->get('/registrar')->assertOk()->assertSee('Full sections')->assertSee('Accepted awaiting finalization');$this->actingAs($this->head)->get('/department-head')->assertOk()->assertSee('Transferee pending evaluations');
  $this->actingAs($this->f['user'])->get('/student')->assertOk()->assertSee('Your student information')->assertSee('IT001')->assertSee('View COR')->assertSee('Professor Example');
  $other=User::factory()->create(['role'=>'student','must_change_password'=>false]);$this->actingAs($other)->get('/student')->assertOk()->assertDontSee('Professor Example')->assertDontSee('IT001');
 }
}
