<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class DemoSeeder extends Seeder {
public function run():void {
if(!app()->environment('local'))throw new \RuntimeException('Demo data is restricted to local development.');
$password=getenv('PLP_DEMO_PASSWORD');if(!$password || strlen($password)<12)throw new \RuntimeException('Set PLP_DEMO_PASSWORD to a development-only password with 12 or more characters.');
DB::transaction(function()use($password){
$insert=fn($table,$data)=>DB::table($table)->insertGetId($data+['created_at'=>now(),'updated_at'=>now()]);
if(DB::table('programs')->where('code','DEMO-IT')->exists())throw new \RuntimeException('Demo data already exists; existing records have been preserved.');
$users=[];foreach(['registrar'=>'Demo Registrar','department_head'=>'Demo Department Head','student'=>'Demo Regular Student','irregular'=>'Demo Irregular Student'] as $role=>$name){$user=new User;$user->forceFill(['name'=>$name,'email'=>$role.'@example.test','password'=>Hash::make($password),'role'=>$role==='irregular'?'student':$role,'must_change_password'=>false])->save();$users[$role]=$user;}
$program=$insert('programs',['code'=>'DEMO-IT','name'=>'Demonstration Information Technology']);$period=$insert('periods',['name'=>'DEMO 2026-2027 Semester 1','academic_year'=>'2026-2027','semester'=>1]);$curriculum=$insert('curriculums',['program_id'=>$program,'name'=>'Demonstration Curriculum 2026']);
$insert('department_head_programs',['user_id'=>$users['department_head']->id,'program_id'=>$program]);
$subject=$insert('subjects',['code'=>'DEMO101','name'=>'Demonstration Computing Fundamentals','units'=>3,'type'=>'lecture','allowed_days'=>'Mon,Tue,Wed,Thu,Fri']);$insert('curriculum_subjects',['curriculum_id'=>$curriculum,'subject_id'=>$subject,'year_level'=>1,'semester'=>1]);
$building=$insert('buildings',['name'=>'Demonstration Building']);$floor=$insert('floors',['building_id'=>$building,'name'=>'3']);
$section=$insert('sections',['program_id'=>$program,'period_id'=>$period,'name'=>'DEMO 1A','year_level'=>1,'capacity'=>40]);$room=$insert('rooms',['code'=>'DEMO301','building'=>'Demonstration Building','floor'=>'3','building_id'=>$building,'floor_id'=>$floor,'type'=>'lecture','capacity'=>40]);
$professor=$insert('professors',['employee_id'=>'DEMO-EMP-1','name'=>'Demonstration Professor','email'=>'professor@example.test','department'=>'DEMO','employment_type'=>'FULL_TIME']);$insert('professor_subjects',['professor_id'=>$professor,'subject_id'=>$subject]);$insert('professor_availabilities',['professor_id'=>$professor,'day'=>'Mon','start_time'=>'07:00','end_time'=>'18:00']);
$offering=$insert('offerings',['subject_id'=>$subject,'section_id'=>$section,'period_id'=>$period,'capacity'=>40]);$insert('schedules',['offering_id'=>$offering,'professor_id'=>$professor,'room_id'=>$room,'day'=>'Mon','start_time'=>'09:00','end_time'=>'12:00']);
foreach(['student'=>'regular','irregular'=>'irregular'] as $key=>$classification)$insert('students',['user_id'=>$users[$key]->id,'admission_id'=>'DEMO-'.strtoupper($key),'student_number'=>'DEMO-'.strtoupper($key),'curriculum_id'=>$curriculum,'year_level'=>1,'classification'=>$classification]);
});
$this->command?->info('Synthetic demo records created. Emails: registrar@example.test, department_head@example.test, student@example.test, irregular@example.test. Password comes from PLP_DEMO_PASSWORD. No real institutional data was used.');
}
}