<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
public function up():void{Schema::table('subjects',function(Blueprint $t){$t->text('description')->nullable();$t->string('department')->nullable();});Schema::table('rooms',function(Blueprint $t){$t->text('equipment')->nullable();$t->foreignId('dedicated_subject_id')->nullable()->constrained('subjects');});Schema::table('prerequisites',fn(Blueprint $t)=>$t->decimal('minimum_grade',6,2)->nullable());}
public function down():void{Schema::table('prerequisites',fn(Blueprint $t)=>$t->dropColumn('minimum_grade'));Schema::table('rooms',function(Blueprint $t){$t->dropConstrainedForeignId('dedicated_subject_id');$t->dropColumn('equipment');});Schema::table('subjects',fn(Blueprint $t)=>$t->dropColumn(['description','department']));}
};