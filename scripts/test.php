<?php
// Windows-compatible PHPUnit entry point. It uses the same phpunit.xml settings.
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);chdir($root);require $root.'/vendor/autoload.php';
$settings=[];foreach(simplexml_load_file($root.'/phpunit.xml')->php->env as $env)$settings[(string)$env['name']]=(string)$env['value'];
if(($argv[1]??'')==='mysql'){
 $local=Dotenv\Dotenv::parse(file_get_contents($root.'/.env'));
 foreach(['DB_HOST','DB_PORT','DB_USERNAME','DB_PASSWORD'] as $key)$settings[$key]=$local[$key]??'';
 $settings['DB_CONNECTION']='mysql';$settings['DB_DATABASE']='plp_automated_testing';
}
foreach($settings as $key=>$value){putenv("$key=$value");$_ENV[$key]=$value;$_SERVER[$key]=$value;}
exit((new PHPUnit\TextUI\Application)->run(['phpunit','--no-configuration','tests']));