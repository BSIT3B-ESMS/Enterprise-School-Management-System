param([Parameter(Mandatory=$true)][string]$Php)
$ErrorActionPreference='Stop'
$runtime=Join-Path $env:LOCALAPPDATA 'PLPEnrollment\runtime'
New-Item -ItemType Directory -Force $runtime | Out-Null
$phpDir=Split-Path $Php
@"
extension_dir="$phpDir\ext"
extension=pdo_sqlite
extension=sqlite3
extension=pdo_mysql
extension=mysqli
extension=mbstring
extension=fileinfo
extension=openssl
extension=curl
extension=zip
extension=gd
date.timezone=Asia/Manila
"@ | Set-Content (Join-Path $runtime 'php.ini')
$env:PHPRC=$runtime
& $Php (Join-Path $PSScriptRoot 'check-runtime.php')
if($LASTEXITCODE -ne 0){throw 'PHP 8.4+ with PDO MySQL, OpenSSL and mbstring is required.'}
