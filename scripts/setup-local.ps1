$ErrorActionPreference='Stop'
$project=Split-Path $PSScriptRoot
Set-Location $project
if(!(Test-Path vendor/autoload.php)){throw 'Run composer install first.'}
$phpCommand=Get-Command php.exe -ErrorAction SilentlyContinue
$php=if($phpCommand){$phpCommand.Source}else{Get-ChildItem (Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages\PHP.PHP.*\php.exe') -ErrorAction SilentlyContinue|Select-Object -First 1 -ExpandProperty FullName}
if(!$php){throw 'Install PHP 8.4+ with PDO MySQL support.'}
& (Join-Path $PSScriptRoot 'configure-php.ps1') -Php $php
if(!(Test-Path .env)){Copy-Item .env.example .env}
$envFile=Get-Content .env -Raw
if($envFile -notmatch '(?m)^DB_CONNECTION="?(mysql|mariadb)"?\s*$'){throw 'Configure MariaDB/MySQL in .env; existing configuration was preserved.'}
& (Join-Path $PSScriptRoot 'start-database.ps1')
Remove-Item Env:DB_CONNECTION,Env:DB_DATABASE -ErrorAction SilentlyContinue
if($envFile -notmatch '(?m)^APP_KEY=.+$'){& $php artisan key:generate --no-interaction;if($LASTEXITCODE -ne 0){throw 'Application key setup failed.'}}
& $php artisan migrate --force
if($LASTEXITCODE -ne 0){throw 'Migrations failed.'}
Write-Output 'MariaDB/MySQL ready. Run start.ps1. No default accounts were created.'
