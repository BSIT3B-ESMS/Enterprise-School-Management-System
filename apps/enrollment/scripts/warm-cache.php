<?php
// Warm Laravel's derived provider caches where Windows access checks are inaccurate.
// No application behavior or vendor sources are changed.
$root=dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app=require $root.'/bootstrap/app.php';
$installed=json_decode(file_get_contents($root.'/vendor/composer/installed.json'),true);
$packages=[];
foreach($installed['packages'] as $package) {
 if(isset($package['extra']['laravel'])) $packages[$package['name']]=$package['extra']['laravel'];
}
file_put_contents($root.'/bootstrap/cache/packages.php','<?php return '.var_export($packages,true).';');
(new Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables)->bootstrap($app);
(new Illuminate\Foundation\Bootstrap\LoadConfiguration)->bootstrap($app);
Illuminate\Foundation\Bootstrap\RegisterProviders::merge(require $root.'/bootstrap/providers.php');
// Bootstrap registration first needs a provider manifest; derive the exact provider order.
$defaults=Illuminate\Support\ServiceProvider::defaultProviders()->merge(require $root.'/bootstrap/providers.php')->toArray();
$configured=$app->make('config')->get('app.providers',$defaults);
$configured=array_values(array_unique(array_merge($configured,require $root.'/bootstrap/providers.php')));
$providers=(new Illuminate\Support\Collection($configured))->partition(fn($p)=>str_starts_with($p,'Illuminate\\'));
$packageProviders=[];
foreach($packages as $package) foreach(($package['providers']??[]) as $provider) $packageProviders[]=$provider;
$providers->splice(1,0,[$packageProviders]);
$list=$providers->collapse()->toArray();
$repo=new class($app,new Illuminate\Filesystem\Filesystem,$root.'/bootstrap/cache/services.php') extends Illuminate\Foundation\ProviderRepository {
 public function generate(array $providers) { return $this->compileManifest($providers); }
 public function writeManifest($manifest) { file_put_contents($this->manifestPath,'<?php return '.var_export($manifest,true).';'); return $manifest; }
};
$repo->generate($list);
echo "Provider caches generated from installed dependencies.\n";