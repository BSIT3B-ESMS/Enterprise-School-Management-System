<?php
// Windows-compatible PHPUnit entry point. It uses the same phpunit.xml settings.
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);chdir($root);require $root.'/vendor/autoload.php';
$settings=[];foreach(simplexml_load_file($root.'/phpunit.xml')->php->env as $env)$settings[(string)$env['name']]=(string)$env['value'];
$mode=$argv[1]??'mysql';if(!in_array($mode,['mysql','sqlite'])){fwrite(STDERR,"Run php scripts/test.php (MariaDB), or php scripts/test.php sqlite for archive compatibility.\n");exit(2);}
$pdo=null;$database=null;
try{
 if($mode==='mysql'){$env=Dotenv\Dotenv::parse(file_get_contents($root.'/.env'));if(!in_array($env['DB_HOST']??'',['127.0.0.1','localhost'])||($env['DB_PORT']??'')!=='3307')throw new RuntimeException('This local test runner requires the isolated local MariaDB server on port 3307.');$admin=json_decode(file_get_contents(getenv('LOCALAPPDATA').'/PLPEnrollment/runtime/admin.json'),true,512,JSON_THROW_ON_ERROR);$pdo=new PDO('mysql:host=127.0.0.1;port=3307;charset=utf8mb4','root',$admin['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$database='plp_test_'.bin2hex(random_bytes(6));$pdo->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$settings=array_merge($settings,['DB_CONNECTION'=>'mysql','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3307','DB_DATABASE'=>$database,'DB_USERNAME'=>'root','DB_PASSWORD'=>$admin['password']]);}
 else{$settings['DB_CONNECTION']='sqlite';$settings['DB_DATABASE']=':memory:';}
 foreach($settings as $key=>$value){putenv("$key=$value");$_ENV[$key]=$value;$_SERVER[$key]=$value;}
 $result=(new PHPUnit\TextUI\Application)->run(['phpunit','--no-configuration','tests']);
}finally{if($pdo&&$database&&preg_match('/^plp_test_[a-f0-9]{12}$/',$database))$pdo->exec("DROP DATABASE `$database`");}
exit($result);
