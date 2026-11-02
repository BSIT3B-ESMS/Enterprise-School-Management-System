<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
public function up():void {
Schema::create('room_closures',function(Blueprint $t){$t->id();$t->foreignId('room_id')->constrained();$t->foreignId('period_id')->constrained();$t->string('day');$t->time('start_time');$t->time('end_time');$t->text('reason');$t->boolean('active')->default(true);$t->timestamps();});
Schema::create('academic_overrides',function(Blueprint $t){$t->id();$t->foreignId('student_id')->constrained();$t->foreignId('subject_id')->constrained();$t->foreignId('period_id')->constrained();$t->foreignId('approved_by')->constrained('users');$t->text('reason');$t->boolean('active')->default(true);$t->timestamps();$t->unique(['student_id','subject_id','period_id']);});
}
public function down():void{Schema::dropIfExists('academic_overrides');Schema::dropIfExists('room_closures');}
};