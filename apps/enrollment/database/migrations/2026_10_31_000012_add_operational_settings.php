<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('system_settings',function(Blueprint $t){$t->string('key')->primary();$t->text('value');$t->timestamps();});
  Schema::table('professors',fn(Blueprint $t)=>$t->timestamp('last_synced_at')->nullable());
  Schema::table('curriculum_subjects',function(Blueprint $t){$t->string('source_title')->nullable();$t->unsignedTinyInteger('minimum_year')->default(0);$t->unsignedSmallInteger('minimum_passed_units')->default(0);});
 }
 public function down():void {
  Schema::table('professors',fn(Blueprint $t)=>$t->dropColumn('last_synced_at'));
  Schema::table('curriculum_subjects',fn(Blueprint $t)=>$t->dropColumn(['source_title','minimum_year','minimum_passed_units']));
  Schema::dropIfExists('system_settings');
 }
};
