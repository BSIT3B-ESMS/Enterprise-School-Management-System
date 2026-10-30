<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {public function up():void{Schema::table('periods',function(Blueprint $t){$t->string('academic_year')->nullable();$t->unsignedTinyInteger('semester')->default(1);});foreach(DB::table('periods')->get() as $period){if(preg_match('/\d{4}-\d{4}/',$period->name,$m))DB::table('periods')->where('id',$period->id)->update(['academic_year'=>$m[0]]);}}public function down():void{Schema::table('periods',fn(Blueprint $t)=>$t->dropColumn(['academic_year','semester']));}};