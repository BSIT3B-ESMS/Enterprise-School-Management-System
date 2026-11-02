<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Support\Facades\{DB,Artisan};
use Symfony\Component\Process\Process;
use Tests\TestCase;
class CapacityConcurrencyTest extends TestCase {
 use \Tests\Support\AcademicFixture;
 private string $database;
 protected function setUp():void {
  parent::setUp();$directory=storage_path('framework/testing');if(!is_dir($directory))mkdir($directory,0700,true);
  if(DB::getDriverName()==='mysql'){$this->database=config('database.connections.mysql.database');if(!preg_match('/^plp_test_[a-f0-9]{12}$/',$this->database))throw new \RuntimeException('Concurrency tests require an isolated test schema.');Artisan::call('migrate:fresh',['--force'=>true]);return;}
  $this->database=$directory.'/capacity-'.bin2hex(random_bytes(8)).'.sqlite';touch($this->database);
  config(['database.connections.sqlite.database'=>$this->database]);DB::purge('sqlite');
  Artisan::call('migrate',['--force'=>true]);
 }
 protected function tearDown():void {
  if(DB::getDriverName()==='mysql'){Artisan::call('migrate:fresh',['--force'=>true]);\Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated=false;parent::tearDown();return;}
  DB::disconnect('sqlite');foreach([$this->database,$this->database.'-wal',$this->database.'-shm'] as $path)if(is_file($path))unlink($path);
  \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated=false;parent::tearDown();
 }
 public function test_competing_finalizations_cannot_take_the_same_last_seat():void {
  $f=$this->fixture();DB::table('offerings')->where('id',$f['offering'])->update(['capacity'=>1]);
  $second=User::factory()->create(['role'=>'student','must_change_password'=>false]);$secondStudent=$this->row('students',['user_id'=>$second->id,'admission_id'=>'ADM-RACE-2','curriculum_id'=>$f['curriculum']]);
  $service=app(EnrollmentService::class);$ids=[];
  foreach([[$f['student'],$f['user']],[$secondStudent,$second]] as [$student,$user]){$this->actingAs($f['registrar']);$id=$service->propose($student,$f['period'],[$f['offering']]);$ids[]=$id;}
  $env=['APP_ENV'=>'testing','APP_KEY'=>config('app.key'),'DB_CONNECTION'=>DB::getDriverName(),'DB_DATABASE'=>$this->database,'CACHE_STORE'=>'array','SESSION_DRIVER'=>'array'];
  if(DB::getDriverName()==='mysql')foreach(['host','port','username','password'] as $key)$env['DB_'.strtoupper($key)]=(string)config('database.connections.mysql.'.$key);
  $processes=[];foreach($ids as $id){$p=new Process([PHP_BINARY,base_path('tests/Support/finalize-worker.php'),(string)$id,(string)$f['registrar']->id],base_path(),$env);$p->setTimeout(20);$p->start();$processes[]=$p;}
  $outputs=[];foreach($processes as $p){$p->wait();$this->assertTrue($p->isSuccessful(),$p->getErrorOutput().$p->getOutput());$outputs[]=trim($p->getOutput());}
  sort($outputs);$this->assertSame(['finalized','rejected'],$outputs);$this->assertDatabaseHas('offerings',['id'=>$f['offering'],'enrolled_count'=>1]);$this->assertSame(1,DB::table('enrollments')->where('status','finalized')->count());$this->assertDatabaseCount('cor_versions',1);
 }
}
