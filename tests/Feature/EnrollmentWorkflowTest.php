<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\AcademicService;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class EnrollmentWorkflowTest extends TestCase {
 use RefreshDatabase, \Tests\Support\AcademicFixture;
 private array $f;
 protected function setUp():void {parent::setUp();$this->f=$this->fixture();}
 private function proposal():int{$this->actingAs($this->f['registrar']);return app(EnrollmentService::class)->propose($this->f['student'],$this->f['period'],[$this->f['offering']]);}
 public function test_regular_student_accepts_and_registrar_finalizes():void {
  $id=$this->proposal();$this->actingAs($this->f['user'])->get('/enrollments/'.$id)->assertOk()->assertSee('IT001');
  $this->get('/enrollments/'.$id.'/cor')->assertNotFound();
  $this->post('/enrollments/'.$id.'/decision',['version'=>1,'decision'=>'accept'])->assertSessionHasNoErrors();
  $this->actingAs($this->f['registrar'])->post('/enrollments/'.$id.'/finalize')->assertSessionHasNoErrors();
  $this->assertDatabaseHas('enrollments',['id'=>$id,'status'=>'finalized']);$this->assertDatabaseHas('offerings',['id'=>$this->f['offering'],'enrolled_count'=>1]);
  $this->actingAs($this->f['user'])->get('/enrollments/'.$id.'/cor')->assertOk()->assertSee('Certificate of Registration');
  DB::table('subjects')->where('id',$this->f['subject'])->update(['name'=>'Changed title']);$this->get('/enrollments/'.$id.'/cor')->assertSee('Introduction to IT')->assertDontSee('Changed title');
 }
 public function test_finalization_requires_current_acceptance():void {$id=$this->proposal();$this->post('/enrollments/'.$id.'/finalize')->assertSessionHasErrors('academic');$this->assertDatabaseCount('cor_versions',0);}
 public function test_stale_version_cannot_be_accepted():void {$id=$this->proposal();$this->proposal();$this->actingAs($this->f['user'])->post('/enrollments/'.$id.'/decision',['version'=>1,'decision'=>'accept'])->assertSessionHasErrors('academic');$this->assertDatabaseHas('enrollments',['id'=>$id,'status'=>'proposed','version'=>2]);}
 public function test_decline_needs_remarks_and_preserves_history():void {$id=$this->proposal();$this->actingAs($this->f['user'])->post('/enrollments/'.$id.'/decision',['version'=>1,'decision'=>'decline'])->assertSessionHasErrors('academic');$this->post('/enrollments/'.$id.'/decision',['version'=>1,'decision'=>'decline','remarks'=>'Request another time.'])->assertSessionHasNoErrors();$this->assertDatabaseHas('enrollment_events',['enrollment_id'=>$id,'action'=>'revision_requested']);$this->assertDatabaseCount('proposal_items',1);}
 public function test_student_cannot_view_another_student_record():void {$id=$this->proposal();$other=User::factory()->create(['role'=>'student','must_change_password'=>false]);$this->actingAs($other)->get('/enrollments/'.$id)->assertForbidden();$this->get('/records/'.$this->f['student'])->assertForbidden();}
 public function test_capacity_is_rechecked_at_finalization():void {$id=$this->proposal();$this->actingAs($this->f['user'])->post('/enrollments/'.$id.'/decision',['version'=>1,'decision'=>'accept']);DB::table('offerings')->where('id',$this->f['offering'])->update(['enrolled_count'=>2]);$this->actingAs($this->f['registrar'])->post('/enrollments/'.$id.'/finalize')->assertSessionHasErrors('academic');$this->assertDatabaseCount('cor_versions',0);$this->assertDatabaseHas('enrollments',['id'=>$id,'status'=>'accepted']);}
 public function test_missing_prerequisite_blocks_proposal():void {$required=$this->row('subjects',['code'=>'PRE','name'=>'Prerequisite','units'=>3]);$this->row('prerequisites',['subject_id'=>$this->f['subject'],'required_subject_id'=>$required]);$this->actingAs($this->f['registrar']);$this->expectException(ValidationException::class);$this->proposal();}
 public function test_circular_prerequisite_is_rejected():void {$other=$this->row('subjects',['code'=>'OTHER','name'=>'Other','units'=>3]);$this->row('prerequisites',['subject_id'=>$this->f['subject'],'required_subject_id'=>$other]);$this->expectException(ValidationException::class);app(AcademicService::class)->prerequisite(['subject_id'=>$other,'required_subject_id'=>$this->f['subject']]);}
 public function test_overlapping_schedule_is_rejected():void {$this->actingAs($this->f['registrar'])->post('/academic/schedules',['offering_id'=>$this->f['offering'],'professor_id'=>$this->f['professor'],'room_id'=>$this->f['room'],'day'=>'Mon','start_time'=>'08:30','end_time'=>'09:30'])->assertSessionHasErrors('academic');}
 public function test_adjacent_schedule_is_allowed():void {$this->actingAs($this->f['registrar'])->post('/academic/schedules',['offering_id'=>$this->f['offering'],'professor_id'=>$this->f['professor'],'room_id'=>$this->f['room'],'day'=>'Mon','start_time'=>'09:00','end_time'=>'10:00'])->assertSessionHasNoErrors();$this->assertDatabaseCount('schedules',2);}
 public function test_unauthorized_professor_and_forbidden_day_are_rejected():void {DB::table('professor_subjects')->delete();$this->actingAs($this->f['registrar'])->post('/academic/schedules',['offering_id'=>$this->f['offering'],'professor_id'=>$this->f['professor'],'room_id'=>$this->f['room'],'day'=>'Tue','start_time'=>'09:00','end_time'=>'10:00'])->assertSessionHasErrors('academic');}
 public function test_head_without_program_assignment_cannot_access_student():void {$head=User::factory()->create(['role'=>'department_head','must_change_password'=>false]);$this->actingAs($head)->get('/records/'.$this->f['student'])->assertForbidden();}
 public function test_irregular_two_stage_approval():void {
  DB::table('students')->where('id',$this->f['student'])->update(['classification'=>'irregular']);
  $head=User::factory()->create(['role'=>'department_head','must_change_password'=>false]);$this->row('department_head_programs',['user_id'=>$head->id,'program_id'=>$this->f['program']]);
  $this->row('special_cases',['student_id'=>$this->f['student'],'period_id'=>$this->f['period'],'request'=>'Back subject','status'=>'approved']);
  $this->actingAs($head);$id=app(EnrollmentService::class)->propose($this->f['student'],$this->f['period'],[$this->f['offering']],true);
  $this->actingAs($this->f['user'])->post('/enrollments/'.$id.'/decision',['version'=>1,'decision'=>'accept'])->assertSessionHasNoErrors();
  $this->assertDatabaseHas('enrollments',['id'=>$id,'status'=>'special_accepted']);
  $this->actingAs($this->f['registrar']);app(EnrollmentService::class)->propose($this->f['student'],$this->f['period'],[$this->f['offering']]);
  $this->actingAs($this->f['user'])->post('/enrollments/'.$id.'/decision',['version'=>2,'decision'=>'accept'])->assertSessionHasNoErrors();
  $this->actingAs($this->f['registrar'])->post('/enrollments/'.$id.'/finalize')->assertSessionHasNoErrors();$this->assertDatabaseHas('enrollments',['id'=>$id,'version'=>2,'status'=>'finalized']);
 }
 public function test_registrar_pages_render():void {$this->actingAs($this->f['registrar']);foreach(['/enrollments','/special-cases','/imports','/scheduler','/records','/activity','/reports','/audit','/staff','/professors','/records/'.$this->f['student']] as $path)$this->get($path)->assertOk();foreach(array_keys(config('academic')) as $resource)$this->get('/academic/'.$resource)->assertOk();}
 public function test_no_mutation_of_class_with_proposal_history():void {$this->proposal();$this->post('/academic/schedules/'.$this->f['schedule'].'/deactivate')->assertSessionHasErrors('academic');$this->assertDatabaseHas('schedules',['id'=>$this->f['schedule'],'active'=>true]);}
 public function test_pdf_download_and_authorized_revision_preserve_cors():void {
  $id=$this->proposal();$this->actingAs($this->f['user'])->post('/enrollments/'.$id.'/decision',['version'=>1,'decision'=>'accept']);$this->actingAs($this->f['registrar'])->post('/enrollments/'.$id.'/finalize');
  $this->get('/enrollments/'.$id.'/pdf')->assertOk()->assertHeader('Content-Type','application/pdf');
  $this->post('/enrollments/'.$id.'/revise',['remarks'=>'Correct class assignment.'])->assertSessionHasNoErrors();
  $this->assertDatabaseHas('enrollments',['id'=>$id,'status'=>'revision_requested']);$this->assertDatabaseHas('offerings',['id'=>$this->f['offering'],'enrolled_count'=>0]);$this->assertDatabaseCount('cor_versions',1);$this->get('/enrollments/'.$id.'/cor/1')->assertOk()->assertSee('Historical finalized copy');$this->get('/enrollments/'.$id.'/cor')->assertNotFound();
  $this->proposal();$this->actingAs($this->f['user'])->post('/enrollments/'.$id.'/decision',['version'=>2,'decision'=>'accept']);$this->actingAs($this->f['registrar'])->post('/enrollments/'.$id.'/finalize')->assertSessionHasNoErrors();$this->assertDatabaseCount('cor_versions',2);
 }
 public function test_inactive_professor_blocks_finalization():void {$id=$this->proposal();$this->actingAs($this->f['user'])->post('/enrollments/'.$id.'/decision',['version'=>1,'decision'=>'accept']);DB::table('professors')->where('id',$this->f['professor'])->update(['active'=>false]);$this->actingAs($this->f['registrar'])->post('/enrollments/'.$id.'/finalize')->assertSessionHasErrors('academic');$this->assertDatabaseCount('cor_versions',0);}
 public function test_unreferenced_room_can_be_edited_and_referenced_room_cannot():void {
  $data=['code'=>'301','building_id'=>$this->f['building'],'floor_id'=>$this->f['floor'],'type'=>'lecture','capacity'=>3];$this->actingAs($this->f['registrar'])->post('/academic/rooms/'.$this->f['room'],$data)->assertSessionHasNoErrors();$this->assertDatabaseHas('rooms',['id'=>$this->f['room'],'capacity'=>3]);$this->proposal();$data['capacity']=1;$this->post('/academic/rooms/'.$this->f['room'],$data)->assertSessionHasErrors('academic');
 }
 public function test_irregular_final_proposal_cannot_bypass_special_acceptance():void {
  DB::table('students')->where('id',$this->f['student'])->update(['classification'=>'irregular']);$this->row('special_cases',['student_id'=>$this->f['student'],'period_id'=>$this->f['period'],'request'=>'Back subject','status'=>'approved']);$this->expectException(ValidationException::class);$this->proposal();
 }
 public function test_import_deduplicates_and_does_not_log_passwords():void {
  $payload=['source'=>'admission','schema_version'=>'1.0','students'=>[['admission_id'=>'ADM-NEW','first_name'=>'Test','last_name'=>'Student','email'=>'new@example.test','program_code'=>'BSIT','year_level'=>1,'admission_status'=>'ADMITTED','temporary_password'=>'TemporaryPass123!']]];
  $this->actingAs($this->f['registrar']);for($i=0;$i<2;$i++){$file=\Illuminate\Http\UploadedFile::fake()->createWithContent('admission.json',json_encode($payload));$this->post('/imports',['source'=>'admission','curriculum_id'=>$this->f['curriculum'],'file'=>$file])->assertSessionHasNoErrors();}
  $this->assertDatabaseCount('students',2);$this->assertDatabaseHas('external_sync_logs',['created'=>1,'rejected'=>0]);$this->assertDatabaseHas('external_sync_logs',['updated'=>1,'rejected'=>0]);$this->assertStringNotContainsString('TemporaryPass123!',json_encode(DB::table('external_sync_logs')->get()));$this->assertStringNotContainsString('TemporaryPass123!',json_encode(DB::table('audit_logs')->get()));
 }
 public function test_invalid_import_json_returns_validation_error():void {
  $this->actingAs($this->f['registrar'])->post('/imports',['source'=>'admission','curriculum_id'=>$this->f['curriculum'],'file'=>\Illuminate\Http\UploadedFile::fake()->createWithContent('bad.json','not json')])->assertSessionHasErrors('academic');
 }
 public function test_room_closure_blocks_class_assignment():void {
  $this->row('room_closures',['room_id'=>$this->f['room'],'period_id'=>$this->f['period'],'day'=>'Mon','start_time'=>'09:00','end_time'=>'10:00','reason'=>'Maintenance']);
  $this->actingAs($this->f['registrar'])->post('/academic/schedules',['offering_id'=>$this->f['offering'],'professor_id'=>$this->f['professor'],'room_id'=>$this->f['room'],'day'=>'Mon','start_time'=>'09:00','end_time'=>'10:00'])->assertSessionHasErrors('academic');
 }
 public function test_authorized_override_is_scoped_and_does_not_bypass_capacity():void {
  $required=$this->row('subjects',['code'=>'PRE','name'=>'Prerequisite','units'=>3]);$this->row('prerequisites',['subject_id'=>$this->f['subject'],'required_subject_id'=>$required]);
  $head=User::factory()->create(['role'=>'department_head','must_change_password'=>false]);$data=['student_id'=>$this->f['student'],'subject_id'=>$this->f['subject'],'period_id'=>$this->f['period'],'reason'=>'Approved according to documented academic authority.'];
  $this->actingAs($head)->post('/academic-overrides',$data)->assertForbidden();$this->row('department_head_programs',['user_id'=>$head->id,'program_id'=>$this->f['program']]);$this->post('/academic-overrides',$data)->assertSessionHasNoErrors();$this->proposal();
  DB::table('offerings')->where('id',$this->f['offering'])->update(['enrolled_count'=>2]);$this->expectException(ValidationException::class);$this->proposal();
 }
 public function test_notifications_are_private():void {
  $id=$this->proposal();$notice=DB::table('portal_notifications')->where('user_id',$this->f['user']->id)->first();$other=User::factory()->create(['role'=>'student','must_change_password'=>false]);$this->actingAs($other)->post('/notifications/'.$notice->id.'/read')->assertNotFound();$this->actingAs($this->f['user'])->get('/notifications')->assertOk()->assertSee('Proposal Sent');$this->post('/notifications/'.$notice->id.'/read')->assertRedirect('/enrollments/'.$id);$this->assertNotNull(DB::table('portal_notifications')->where('id',$notice->id)->value('read_at'));
 }
 public function test_import_without_password_sends_one_time_setup_notification():void {
  \Illuminate\Support\Facades\Notification::fake();$payload=['source'=>'admission','schema_version'=>'1.0','students'=>[['admission_id'=>'INVITE-1','first_name'=>'Invite','last_name'=>'Example','email'=>'invited@example.test','program_code'=>'BSIT','year_level'=>1,'admission_status'=>'ADMITTED']]];
  $this->actingAs($this->f['registrar'])->post('/imports',['source'=>'admission','curriculum_id'=>$this->f['curriculum'],'file'=>\Illuminate\Http\UploadedFile::fake()->createWithContent('invite.json',json_encode($payload))])->assertSessionHasNoErrors();$user=User::where('email','invited@example.test')->first();$this->assertNotNull($user);\Illuminate\Support\Facades\Notification::assertSentTo($user,\Illuminate\Auth\Notifications\ResetPassword::class);
 }
 public function test_staff_creation_is_controlled_and_password_is_hashed():void {
  $data=['name'=>'Example Staff','email'=>'staff@example.test','role'=>'department_head','password'=>'ExampleStaffPass123','password_confirmation'=>'ExampleStaffPass123'];$this->actingAs($this->f['user'])->post('/staff',$data)->assertForbidden();$this->actingAs($this->f['registrar'])->post('/staff',$data)->assertSessionHasNoErrors();$user=User::where('email',$data['email'])->first();$this->assertTrue($user->must_change_password);$this->assertTrue(\Illuminate\Support\Facades\Hash::check($data['password'],$user->password));$this->assertStringNotContainsString($data['password'],json_encode(DB::table('audit_logs')->get()));
 }
 public function test_minimum_prerequisite_grade_uses_configured_grade_direction():void {
  config(['enrollment.grade_order'=>'lower']);$required=$this->row('subjects',['code'=>'PRE-GRADE','name'=>'Prerequisite grade','units'=>3]);$this->row('prerequisites',['subject_id'=>$this->f['subject'],'required_subject_id'=>$required,'minimum_grade'=>2.0]);$record=$this->row('academic_records',['student_id'=>$this->f['student'],'subject_id'=>$required,'period_id'=>$this->f['period'],'status'=>'PASSED','grade'=>'2.50']);
  try{$this->proposal();$this->fail('A passing grade below the prerequisite threshold must be rejected.');}catch(ValidationException){$this->assertDatabaseCount('enrollments',0);}
  DB::table('academic_records')->where('id',$record)->update(['grade'=>'1.75']);$this->proposal();$this->assertDatabaseCount('enrollments',1);
 }
 public function test_staff_deactivation_revokes_tokens_and_blocks_login():void {
  $other=User::factory()->create(['role'=>'department_head','must_change_password'=>false,'password'=>\Illuminate\Support\Facades\Hash::make('StaffPassword123')]);$other->createToken('staff',['portal']);$this->actingAs($this->f['registrar'])->post('/staff/'.$other->id.'/toggle')->assertSessionHasNoErrors();$this->assertFalse($other->fresh()->active);$this->assertSame(0,$other->tokens()->count());$this->post('/logout');$this->post('/login',['email'=>$other->email,'password'=>'StaffPassword123'])->assertSessionHasErrors('email');$this->assertGuest();
 }
 public function test_ajax_schedule_validation_returns_json():void {
  $this->actingAs($this->f['registrar'])->postJson('/academic/schedules',['offering_id'=>$this->f['offering'],'professor_id'=>$this->f['professor'],'room_id'=>$this->f['room'],'day'=>'Mon','start_time'=>'08:30','end_time'=>'09:30'])->assertUnprocessable()->assertJsonValidationErrors('academic');
 }
 public function test_report_export_filters_and_neutralizes_spreadsheet_formulas():void {
  $id=$this->proposal();DB::table('users')->where('id',$this->f['user']->id)->update(['name'=>'=1+1']);$response=$this->get('/reports?status=proposed&download=csv')->assertOk()->assertHeader('Content-Type','text/csv; charset=UTF-8');$this->assertStringContainsString("'=1+1",$response->streamedContent());$this->get('/reports?status=finalized')->assertOk()->assertDontSee('=1+1');
 }
 public function test_registrar_can_create_subject_with_valid_days():void {
  $data=['code'=>'NEW101','name'=>'New subject','units'=>3,'type'=>'lecture','allowed_days'=>'Mon,Wed,Fri'];$this->actingAs($this->f['registrar'])->post('/academic/subjects',$data)->assertSessionHasNoErrors();$this->assertDatabaseHas('subjects',['code'=>'NEW101','allowed_days'=>'Mon,Wed,Fri']);$data['code']='BAD101';$data['allowed_days']='Monday,XYZ';$this->post('/academic/subjects',$data)->assertSessionHasErrors('academic');
 }
 public function test_regular_student_cannot_skip_prescribed_year_or_semester():void {
  DB::table('curriculum_subjects')->where('subject_id',$this->f['subject'])->update(['semester'=>2]);$this->expectException(ValidationException::class);$this->proposal();
 }
 public function test_section_capacity_counts_unique_students_across_different_classes():void {
  DB::table('sections')->where('id',$this->f['section'])->update(['capacity'=>1]);DB::table('offerings')->where('id',$this->f['offering'])->update(['capacity'=>1]);$id=$this->proposal();$this->actingAs($this->f['user']);app(EnrollmentService::class)->decide($id,1,true,null);$this->actingAs($this->f['registrar']);app(EnrollmentService::class)->finalize($id);
  $subject=$this->row('subjects',['code'=>'BLOCK2','name'=>'Another subject','units'=>3,'type'=>'lecture','allowed_days'=>'Mon']);$this->row('curriculum_subjects',['curriculum_id'=>$this->f['curriculum'],'subject_id'=>$subject,'year_level'=>1,'semester'=>1]);$this->row('professor_subjects',['professor_id'=>$this->f['professor'],'subject_id'=>$subject]);$offering=$this->row('offerings',['subject_id'=>$subject,'section_id'=>$this->f['section'],'period_id'=>$this->f['period'],'capacity'=>1]);$this->row('schedules',['offering_id'=>$offering,'professor_id'=>$this->f['professor'],'room_id'=>$this->f['room'],'day'=>'Mon','start_time'=>'09:00','end_time'=>'10:00']);
  $user=User::factory()->create(['role'=>'student','must_change_password'=>false]);$student=$this->row('students',['user_id'=>$user->id,'admission_id'=>'BLOCK-NEW','curriculum_id'=>$this->f['curriculum']]);$this->expectException(ValidationException::class);app(EnrollmentService::class)->propose($student,$this->f['period'],[$offering]);
 }
}
