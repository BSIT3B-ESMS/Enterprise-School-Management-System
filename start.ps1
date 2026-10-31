param([int]$Port=8000)
$ErrorActionPreference='Stop'
Set-Location $PSScriptRoot
$runtime=Join-Path $env:LOCALAPPDATA 'PLPEnrollment\runtime'
$phpCommand=Get-Command php.exe -ErrorAction SilentlyContinue
$php=if($phpCommand){$phpCommand.Source}else{Get-ChildItem (Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages\PHP.PHP.*\php.exe') -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty FullName}
if(!$php){throw 'Install PHP 8.4+ and add it to PATH.'}
$phpDirectory=Split-Path $php
& (Join-Path $PSScriptRoot 'scripts\configure-php.ps1') -Php $php
$env:PHPRC=$runtime
$env:PATH=(Split-Path $php)+';'+$env:PATH
if(!(Test-Path vendor/autoload.php)){throw 'Run composer install first.'}
if(!(Test-Path .env)){throw 'Configure .env first. See docs/LOCAL-SETUP.md.'}
# Explicit local server settings must come from this project, not a previous preview shell.
Remove-Item Env:DB_CONNECTION,Env:DB_DATABASE -ErrorAction SilentlyContinue
& (Join-Path $PSScriptRoot 'scripts/start-database.ps1')
& $php scripts/warm-cache.php
if($LASTEXITCODE -ne 0){throw 'Provider cache setup failed.'}
& $php artisan migrate --force
if($LASTEXITCODE -ne 0){throw 'Database migration failed.'}
$listener=Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if($listener){Write-Output "Port $Port is already in use. Open http://127.0.0.1:$Port or choose -Port.";exit 0}
$router=Join-Path $PWD 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
Start-Process $php -ArgumentList @('-S',"127.0.0.1:$Port",('"'+$router+'"')) -WorkingDirectory (Join-Path $PWD 'public') -WindowStyle Hidden -RedirectStandardOutput (Join-Path $PWD 'storage\logs\local-server.out.log') -RedirectStandardError (Join-Path $PWD 'storage\logs\local-server.err.log') | Out-Null
Write-Output "Enrollment system started at http://127.0.0.1:$Port"
