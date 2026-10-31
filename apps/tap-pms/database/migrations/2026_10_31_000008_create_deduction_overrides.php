<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('deduction_overrides',function(Blueprint $t){$t->id();$t->foreignId('employee_id')->constrained();$t->date('effective_from');$t->date('effective_to')->nullable();foreach(['sss','philhealth','pagibig','tax','loan'] as $name)$t->decimal($name,12,2)->nullable();$t->text('reason');$t->foreignId('created_by')->constrained('users');$t->timestamps();$t->unique(['employee_id','effective_from']);});}
 public function down():void {Schema::dropIfExists('deduction_overrides');}
};
