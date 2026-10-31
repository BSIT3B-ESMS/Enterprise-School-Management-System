<?php
$path=getenv('DB_DATABASE');$directory=realpath(__DIR__.'/../../storage/framework/testing');
$isolated=getenv('DB_CONNECTION')==='mysql'?preg_match('/^plp_test_[a-f0-9]{12}$/',$path?:''):($directory&&realpath(dirname($path?:''))===$directory&&preg_match('/^capacity-[a-f0-9]{16}\.sqlite$/',basename($path?:'')));
if(PHP_SAPI!=='cli'||getenv('APP_ENV')!=='testing'||!$isolated)exit(2);
require dirname(__DIR__,2).'/vendor/autoload.php';$app=require dirname(__DIR__,2).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();auth()->loginUsingId((int)$argv[2]);
try{app(App\Services\EnrollmentService::class)->decide((int)$argv[1],1,true,null);echo 'finalized';}catch(Illuminate\Validation\ValidationException){echo 'rejected';}
