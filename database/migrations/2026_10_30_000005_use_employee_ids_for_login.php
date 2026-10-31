<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::table('users',function(Blueprint $table){
   $table->string('login_id',50)->nullable()->unique();
   $table->string('email',190)->nullable()->change();
  });
  // Preserve existing users. Admin IDs are app-local, employee IDs come strictly from EMS.
  DB::table('users')->where('role','admin')->whereNull('login_id')->orderBy('id')->chunkById(100,function($users){
   foreach($users as $u)DB::table('users')->where('id',$u->id)->update(['login_id'=>'ADMIN-'.str_pad((string)$u->id,5,'0',STR_PAD_LEFT)]);
  });
  DB::table('users')->where('role','employee')->whereNull('login_id')->whereNotNull('employee_id')->orderBy('id')->chunkById(100,function($users){
   foreach($users as $u){
    $number=DB::table('employees')->where('id',$u->employee_id)->value('employee_no');
    if($number&&!DB::table('users')->where('login_id',$number)->exists()){
     DB::table('users')->where('id',$u->id)->update(['login_id'=>$number]);
    }
   }
  });
 }
 public function down(): void {
  // Do not silently invent email addresses when reversing the migration.
  if(DB::table('users')->whereNull('email')->exists()){
   throw new RuntimeException('Cannot restore mandatory emails: accounts without email exist.');
  }
  Schema::table('users',function(Blueprint $table){
   $table->dropUnique(['login_id']);
   $table->dropColumn('login_id');
   $table->string('email',190)->nullable(false)->change();
  });
 }
};
