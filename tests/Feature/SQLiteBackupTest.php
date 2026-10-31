<?php
namespace Tests\Feature;
use App\Services\BackupService;
use Illuminate\Support\Facades\{DB,Artisan};
use Tests\TestCase;
class SQLiteBackupTest extends TestCase {
 private string $database;
 private array $files=[];
 protected function setUp():void {
  parent::setUp();$directory=storage_path('framework/testing');if(!is_dir($directory))mkdir($directory,0700,true);
  $this->database=$directory.'/backup-'.bin2hex(random_bytes(8)).'.sqlite';touch($this->database);
  config(['database.default'=>'sqlite','database.connections.sqlite.database'=>$this->database]);DB::purge('sqlite');Artisan::call('migrate',['--force'=>true]);
 }
 protected function tearDown():void {
  DB::disconnect('sqlite');foreach(array_merge($this->files,[$this->database,$this->database.'-wal',$this->database.'-shm']) as $path)if(is_file($path))unlink($path);
  \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated=false;parent::tearDown();
 }
 public function test_online_backup_includes_wal_commits_and_restores_without_touching_source():void {
  DB::table('programs')->insert(['code'=>'BACKUP-TEST','name'=>'Verified record','active'=>true]);
  $this->assertSame('wal',strtolower(DB::selectOne('PRAGMA journal_mode')->journal_mode));
  $service=app(BackupService::class);$path=$service->create();$this->files[]=$path;
  $this->assertStringEndsWith('.sqlite.enc',$path);$this->assertStringNotContainsString('Verified record',file_get_contents($path));
  $result=$service->verify($path);$this->files[]=$result['target'];$this->assertNotSame($this->database,$result['target']);
  $restore=new \SQLite3($result['target'],SQLITE3_OPEN_READONLY);$this->assertSame('Verified record',$restore->querySingle("SELECT name FROM programs WHERE code='BACKUP-TEST'"));$restore->close();
  $this->assertDatabaseHas('programs',['code'=>'BACKUP-TEST']);
 }
 public function test_tampered_backup_is_rejected_before_restoration():void {
  $service=app(BackupService::class);$path=$service->create();$this->files[]=$path;file_put_contents($path,'corrupted'.file_get_contents($path));
  $this->expectException(\Illuminate\Contracts\Encryption\DecryptException::class);$service->verify($path);
 }
}
