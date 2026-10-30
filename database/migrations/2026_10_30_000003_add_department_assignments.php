<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void {Schema::create('department_head_programs',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained();$t->foreignId('program_id')->constrained();$t->boolean('active')->default(true);$t->timestamps();$t->unique(['user_id','program_id']);});}public function down():void{Schema::dropIfExists('department_head_programs');}};