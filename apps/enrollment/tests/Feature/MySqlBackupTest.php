<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\{DB,Artisan};
use App\Services\BackupService;
class MySqlBackupTest extends TestCase {
 public function test_encrypted_mariadb_backup_restores_in_a_separate_schema():void{
  if(DB::getDriverName()!=='mysql')$this->markTestSkipped('MariaDB backup is verified by the default MariaDB test run.');
  $source=config('database.connections.mysql.database');$this->assertMatchesRegularExpression('/^plp_test_[a-f0-9]{12}$/',$source);Artisan::call('migrate:fresh',['--force'=>true]);DB::table('programs')->insert(['code'=>'VERIFY-BACKUP','name'=>'Restorable fixture','active'=>true]);$path=null;$target=null;
  try{$path=app(BackupService::class)->create();$this->assertStringEndsWith('.sql.enc',$path);$this->assertStringNotContainsString('Restorable fixture',file_get_contents($path));$result=app(BackupService::class)->verify($path);$target=$result['target'];$this->assertNotSame($source,$target);$this->assertSame('Restorable fixture',DB::selectOne('SELECT name FROM `'.$target.'`.programs WHERE code=?',['VERIFY-BACKUP'])->name);$this->assertDatabaseHas('programs',['code'=>'VERIFY-BACKUP']);file_put_contents($path,'tampered');try{app(BackupService::class)->verify($path);$this->fail('Corrupted backup must not be restored.');}catch(\Illuminate\Contracts\Encryption\DecryptException){$this->assertTrue(true);}}
  finally{if($target&&preg_match('/^plp_restore_check_[a-f0-9]{16}$/',$target))DB::statement('DROP DATABASE `'.$target.'`');if($path&&is_file($path))unlink($path);Artisan::call('migrate:fresh',['--force'=>true]);\Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated=false;}
 }
}
