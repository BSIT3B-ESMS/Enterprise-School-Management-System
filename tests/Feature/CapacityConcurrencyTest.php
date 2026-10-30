<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;
class CapacityConcurrencyTest extends TestCase {
 use DatabaseMigrations, \Tests\Support\AcademicFixture;
 protected function tearDown():void {parent::tearDown();\Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated=false;}
 public function test_competing_finalizations_cannot_take_the_same_last_seat():void {
  if(DB::getDriverName()!=='mysql')$this->markTestSkipped('Requires MariaDB row locks and independent processes.');
  $f=$this->fixture();DB::table('offerings')->where('id',$f['offering'])->update(['capacity'=>1]);
  $second=User::factory()->create(['role'=>'student','must_change_password'=>false]);$secondStudent=$this->row('students',['user_id'=>$second->id,'admission_id'=>'ADM-RACE-2','curriculum_id'=>$f['curriculum']]);
  $service=app(EnrollmentService::class);$ids=[];
  foreach([[$f['student'],$f['user']],[$secondStudent,$second]] as [$student,$user]){$this->actingAs($f['registrar']);$id=$service->propose($student,$f['period'],[$f['offering']]);$this->actingAs($user);$service->decide($id,1,true,null);$ids[]=$id;}
  $connection=config('database.connections.mysql');$env=['APP_ENV'=>'testing','APP_KEY'=>config('app.key'),'DB_CONNECTION'=>'mysql','DB_HOST'=>$connection['host'],'DB_PORT'=>(string)$connection['port'],'DB_DATABASE'=>'plp_automated_testing','DB_USERNAME'=>$connection['username'],'DB_PASSWORD'=>$connection['password'],'CACHE_STORE'=>'array','SESSION_DRIVER'=>'array'];
  $processes=[];foreach($ids as $id){$p=new Process([PHP_BINARY,base_path('tests/Support/finalize-worker.php'),(string)$id,(string)$f['registrar']->id],base_path(),$env);$p->setTimeout(20);$p->start();$processes[]=$p;}
  $outputs=[];foreach($processes as $p){$p->wait();$this->assertTrue($p->isSuccessful(),$p->getErrorOutput().$p->getOutput());$outputs[]=trim($p->getOutput());}
  sort($outputs);$this->assertSame(['finalized','rejected'],$outputs);$this->assertDatabaseHas('offerings',['id'=>$f['offering'],'enrolled_count'=>1]);$this->assertSame(1,DB::table('enrollments')->where('status','finalized')->count());$this->assertDatabaseCount('cor_versions',1);
 }
}