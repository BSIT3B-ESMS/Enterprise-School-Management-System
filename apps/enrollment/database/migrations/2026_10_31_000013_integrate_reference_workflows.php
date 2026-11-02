<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::create('colleges',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->string('abbreviation')->nullable();$t->boolean('active')->default(true);$t->timestamps();});
  Schema::table('programs',fn(Blueprint $t)=>$t->foreignId('college_id')->nullable()->constrained());
  Schema::table('subjects',function(Blueprint $t){$t->string('scope')->default('program');$t->foreignId('college_id')->nullable()->constrained();$t->foreignId('program_id')->nullable()->constrained();$t->decimal('weekly_hours',4,1)->nullable();});
  Schema::table('periods',function(Blueprint $t){$t->date('start_date')->nullable();$t->date('end_date')->nullable();$t->boolean('is_current')->default(false);$t->timestamp('closed_at')->nullable();});
  Schema::table('students',function(Blueprint $t){$t->unsignedSmallInteger('admission_year')->nullable();});
  Schema::create('student_sections',function(Blueprint $t){$t->id();$t->foreignId('student_id')->constrained();$t->foreignId('period_id')->constrained();$t->foreignId('section_id')->constrained();$t->timestamps();$t->unique(['student_id','period_id']);});
  Schema::create('student_promotions',function(Blueprint $t){$t->id();$t->foreignId('student_id')->constrained();$t->foreignId('period_id')->constrained();$t->string('academic_year');$t->unsignedTinyInteger('from_year');$t->timestamps();$t->unique(['student_id','academic_year']);});
  Schema::create('schedule_locks',function(Blueprint $t){$t->id();$t->foreignId('period_id')->constrained();$t->unsignedTinyInteger('year_level');$t->foreignId('user_id')->constrained();$t->timestamps();$t->unique(['period_id','year_level']);});
  Schema::create('subject_waiting_list',function(Blueprint $t){$t->id();$t->foreignId('student_id')->constrained();$t->foreignId('subject_id')->constrained();$t->foreignId('requested_period_id')->constrained('periods');$t->foreignId('released_case_id')->nullable()->constrained('special_cases');$t->string('status')->default('waiting');$t->text('reason');$t->timestamps();$t->index(['student_id','status']);});
  Schema::table('users',fn(Blueprint $t)=>$t->enum('role',['student','registrar','department_head','professor'])->default('student')->change());
  Schema::table('professors',fn(Blueprint $t)=>$t->foreignId('user_id')->nullable()->unique()->constrained());
  Schema::table('academic_records',function(Blueprint $t){$t->foreignId('submitted_by')->nullable()->constrained('users');$t->timestamp('submitted_at')->nullable();});
  foreach(DB::table('students')->get() as $student)if(preg_match('/ADM-(\d{4})/',$student->admission_id,$m))DB::table('students')->where('id',$student->id)->update(['admission_year'=>(int)$m[1]]);
  $reference=json_decode(file_get_contents(resource_path('data/plp-reference-2022.json')),true);
  foreach($reference['programs'] as $program){$name=$program['college'];DB::table('colleges')->insertOrIgnore(['name'=>$name,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);DB::table('programs')->where('code',$program['code'])->whereNull('college_id')->update(['college_id'=>DB::table('colleges')->where('name',$name)->value('id')]);}
 }
 public function down():void {
  Schema::table('academic_records',fn(Blueprint $t)=>$t->dropColumn(['submitted_by','submitted_at']));
  Schema::table('professors',fn(Blueprint $t)=>$t->dropColumn('user_id'));
  Schema::table('users',fn(Blueprint $t)=>$t->enum('role',['student','registrar','department_head'])->default('student')->change());
  foreach(['subject_waiting_list','schedule_locks','student_sections','student_promotions'] as $table)Schema::dropIfExists($table);
  Schema::table('students',fn(Blueprint $t)=>$t->dropColumn('admission_year'));
  Schema::table('periods',fn(Blueprint $t)=>$t->dropColumn(['start_date','end_date','is_current','closed_at']));
  Schema::table('subjects',fn(Blueprint $t)=>$t->dropColumn(['scope','college_id','program_id','weekly_hours']));
  Schema::table('programs',fn(Blueprint $t)=>$t->dropColumn('college_id'));Schema::dropIfExists('colleges');
 }
};
