<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::table('special_cases', function(Blueprint $t) {
   $t->foreignId('curriculum_id')->nullable()->constrained('curriculums');
   $t->text('requested_subject_ids')->nullable();
   $t->timestamp('forwarded_at')->nullable();
  });
  DB::table('special_cases')->orderBy('id')->each(function($case) {
   DB::table('special_cases')->where('id',$case->id)->update(['curriculum_id'=>DB::table('students')->where('id',$case->student_id)->value('curriculum_id')]);
  });
  Schema::create('credit_evaluations', function(Blueprint $t) {
   $t->id(); $t->foreignId('special_case_id')->constrained();
   $t->foreignId('subject_id')->constrained();
   $t->string('source_institution'); $t->string('source_subject');
   $t->string('source_grade',30)->nullable(); $t->decimal('source_units',5,2);
   $t->string('evidence_reference'); $t->text('reason');
   $t->enum('decision',['approved','rejected']);
   $t->foreignId('academic_record_id')->nullable()->constrained();
   $t->timestamps(); $t->unique(['special_case_id','subject_id']);
  });
  Schema::create('curriculum_assignments', function(Blueprint $t) {
   $t->id(); $t->foreignId('student_id')->constrained();
   $t->foreignId('previous_curriculum_id')->constrained('curriculums');
   $t->foreignId('curriculum_id')->constrained('curriculums');
   $t->foreignId('user_id')->constrained(); $t->text('reason'); $t->timestamps();
  });
  Schema::table('offerings',fn(Blueprint $t)=>$t->string('allowed_days')->nullable());
  Schema::table('curriculums',fn(Blueprint $t)=>$t->string('academic_year',9)->nullable());
 }
 public function down(): void {
  Schema::table('curriculums',fn(Blueprint $t)=>$t->dropColumn('academic_year'));
  Schema::table('offerings',fn(Blueprint $t)=>$t->dropColumn('allowed_days'));
  Schema::dropIfExists('curriculum_assignments'); Schema::dropIfExists('credit_evaluations');
  Schema::table('special_cases',function(Blueprint $t){$t->dropConstrainedForeignId('curriculum_id');$t->dropColumn(['requested_subject_ids','forwarded_at']);});
 }
};
