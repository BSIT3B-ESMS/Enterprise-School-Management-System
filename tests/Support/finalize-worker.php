<?php
if(PHP_SAPI!=='cli' || getenv('APP_ENV')!=='testing' || getenv('DB_DATABASE')!=='plp_automated_testing')exit(2);
require dirname(__DIR__,2).'/vendor/autoload.php';$app=require dirname(__DIR__,2).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();auth()->loginUsingId((int)$argv[2]);
try{app(App\Services\EnrollmentService::class)->finalize((int)$argv[1]);echo 'finalized';}catch(Illuminate\Validation\ValidationException){echo 'rejected';}