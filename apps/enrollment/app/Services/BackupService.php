<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class BackupService {
 private function directory():string {
  $directory=storage_path('app/private/backups');
  if(!is_dir($directory)&&!mkdir($directory,0700,true))throw new \RuntimeException('Cannot create the backup directory.');
  return $directory;
 }
 private function inspect(string $path):array {
  $db=new \SQLite3($path,SQLITE3_OPEN_READONLY);$db->enableExceptions(true);
  try{
   if($db->querySingle('PRAGMA integrity_check')!=='ok')throw new \RuntimeException('SQLite integrity check failed.');
   if($db->query('PRAGMA foreign_key_check')->fetchArray(SQLITE3_ASSOC))throw new \RuntimeException('SQLite foreign-key check failed.');
   $result=$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");$counts=[];
   while($row=$result->fetchArray(SQLITE3_ASSOC)){$name=$row['name'];$counts[$name]=$db->querySingle('SELECT COUNT(*) FROM "'.str_replace('"','""',$name).'"');}
   foreach(['migrations','users','students','cor_versions'] as $table)if(!array_key_exists($table,$counts))throw new \RuntimeException('Required backup table is missing: '.$table);
   return $counts;
  }finally{$db->close();}
 }
 public function create():string {
  if(DB::getDriverName()==='mysql')return app(MySqlBackupService::class)->create();
  if(DB::getDriverName()!=='sqlite')throw new \RuntimeException('This installation supports SQLite backups only.');
  $database=DB::selectOne('PRAGMA database_list')->file;if(!is_file($database))throw new \RuntimeException('Backup requires a file-backed SQLite database.');
  $directory=$this->directory();$temporary=tempnam($directory,'snapshot-');
  try{
   $source=new \SQLite3($database,SQLITE3_OPEN_READONLY);$source->enableExceptions(true);$source->busyTimeout(10000);
   $target=new \SQLite3($temporary);$target->enableExceptions(true);
   try{if(!$source->backup($target))throw new \RuntimeException('SQLite online backup failed.');}finally{$target->close();$source->close();}
   $counts=$this->inspect($temporary);
   // ponytail: snapshots are encrypted in memory; use streaming encryption for databases approaching PHP memory limits.
   $payload=['schema'=>2,'driver'=>'sqlite','created_at'=>now()->toIso8601String(),'counts'=>$counts,'sha256'=>hash_file('sha256',$temporary),'sqlite'=>base64_encode(file_get_contents($temporary))];
   $encrypted=encrypt($payload);if(decrypt($encrypted)!==$payload)throw new \RuntimeException('Backup encryption verification failed.');
   $path=$directory.'/enrollment-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sqlite.enc';
   $handle=fopen($path,'x');if(!$handle)throw new \RuntimeException('Cannot publish a unique backup.');
   try{if(fwrite($handle,$encrypted)!==strlen($encrypted))throw new \RuntimeException('Backup write failed.');}finally{fclose($handle);}
   if(decrypt(file_get_contents($path))!==$payload)throw new \RuntimeException('Written backup verification failed.');
   return $path;
  }finally{if(is_file($temporary))unlink($temporary);}
 }
 public function verify(string $path):array {
  $payload=decrypt(file_get_contents($path));
  if(($payload['driver']??null)==='mysql')return app(MySqlBackupService::class)->verify($path);
  if(($payload['schema']??null)!==2||($payload['driver']??null)!=='sqlite')throw new \RuntimeException('Expected a SQLite backup. Earlier SQL backups remain archived and are not converted by this command.');
  $bytes=base64_decode($payload['sqlite']??'',true);
  if($bytes===false||!hash_equals($payload['sha256']??'',hash('sha256',$bytes)))throw new \RuntimeException('Backup checksum failed.');
  $target=$this->directory().'/restore-check-'.bin2hex(random_bytes(8)).'.sqlite';
  $handle=fopen($target,'x');if(!$handle)throw new \RuntimeException('Cannot create an isolated restore copy.');
  try{if(fwrite($handle,$bytes)!==strlen($bytes))throw new \RuntimeException('Restore write failed.');}finally{fclose($handle);}
  try{$counts=$this->inspect($target);if($counts!==$payload['counts'])throw new \RuntimeException('Restored row counts do not match the snapshot.');}catch(\Throwable $e){unlink($target);throw $e;}
  return ['tables'=>count($counts),'source_created_at'=>$payload['created_at'],'target'=>$target];
 }
}
