<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
public function up():void {
Schema::create('buildings',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->boolean('active')->default(true);$t->timestamps();});
Schema::create('floors',function(Blueprint $t){$t->id();$t->foreignId('building_id')->constrained();$t->string('name');$t->boolean('active')->default(true);$t->timestamps();$t->unique(['building_id','name']);});
Schema::table('rooms',function(Blueprint $t){$t->foreignId('building_id')->nullable()->constrained();$t->foreignId('floor_id')->nullable()->constrained();});
foreach(DB::table('rooms')->get() as $room){DB::table('buildings')->updateOrInsert(['name'=>$room->building],['active'=>true,'updated_at'=>now()]);$building=DB::table('buildings')->where('name',$room->building)->value('id');DB::table('floors')->updateOrInsert(['building_id'=>$building,'name'=>$room->floor],['active'=>true,'updated_at'=>now()]);$floor=DB::table('floors')->where(['building_id'=>$building,'name'=>$room->floor])->value('id');DB::table('rooms')->where('id',$room->id)->update(['building_id'=>$building,'floor_id'=>$floor]);}
}
public function down():void{Schema::table('rooms',function(Blueprint $t){$t->dropConstrainedForeignId('floor_id');$t->dropConstrainedForeignId('building_id');});Schema::dropIfExists('floors');Schema::dropIfExists('buildings');}
};