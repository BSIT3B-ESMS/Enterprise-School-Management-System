<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {public function up():void{Schema::create('employee_histories',function(Blueprint $t){$t->id();$t->foreignId('employee_id')->constrained();$t->string('field');$t->text('old_value')->nullable();$t->text('new_value')->nullable();$t->timestamp('source_updated_at')->nullable();$t->timestamp('observed_at')->useCurrent();;$t->index(['employee_id','observed_at']);});}public function down():void{Schema::dropIfExists('employee_histories');}};
