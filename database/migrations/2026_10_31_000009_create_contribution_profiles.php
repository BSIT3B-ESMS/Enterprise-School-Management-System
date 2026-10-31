<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('deduction_overrides',fn(Blueprint $t)=>$t->decimal('gsis',12,2)->nullable());Schema::create('contribution_profiles',function(Blueprint $t){$t->id();$t->foreignId('employee_id')->constrained();$t->date('effective_from');$t->date('effective_to')->nullable();$t->string('pension_scheme',20);$t->boolean('philhealth_enabled')->default(true);$t->boolean('pagibig_enabled')->default(true);$t->boolean('withholding_enabled')->default(true);$t->text('reason');$t->foreignId('created_by')->constrained('users');$t->timestamps();$t->unique(['employee_id','effective_from']);});}
 public function down():void {Schema::dropIfExists('contribution_profiles');Schema::table('deduction_overrides',fn(Blueprint $t)=>$t->dropColumn('gsis'));}
};
