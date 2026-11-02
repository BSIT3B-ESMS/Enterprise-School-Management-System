$ErrorActionPreference='Stop'
$project=Split-Path $PSScriptRoot
$settings=@{}
foreach($line in Get-Content (Join-Path $project '.env')){if($line -match '^([A-Z_]+)=(.*)$'){$settings[$matches[1]]=$matches[2].Trim('"',"'")}}
if($settings['DB_CONNECTION'] -notin @('mysql','mariadb')){throw 'Configure MariaDB/MySQL in .env.'}
if($settings['DB_HOST'] -notin @('127.0.0.1','localhost') -or $settings['DB_PORT'] -ne '3307'){return}
if(Get-NetTCPConnection -LocalPort 3307 -State Listen -ErrorAction SilentlyContinue){return}
$runtime=Join-Path $env:LOCALAPPDATA 'PLPEnrollment/runtime'
$server=Join-Path $runtime 'mariadb-11.4.9-winx64/bin/mariadbd.exe'
$dataDirectory=Join-Path $runtime 'mariadb-data'
if(!(Test-Path -LiteralPath $server) -or !(Test-Path -LiteralPath $dataDirectory)){throw 'The configured local MariaDB installation is missing. Install MariaDB or configure an existing MySQL server in .env.'}
Start-Process $server -ArgumentList @('--no-defaults',('--datadir="'+$dataDirectory+'"'),'--port=3307','--bind-address=127.0.0.1',('--log-error="'+(Join-Path $runtime 'mariadb.err')+'"')) -WindowStyle Hidden | Out-Null
for($attempt=0;$attempt -lt 30;$attempt++){if(Get-NetTCPConnection -LocalPort 3307 -State Listen -ErrorAction SilentlyContinue){return};Start-Sleep -Milliseconds 200}
throw 'MariaDB did not start. Check its private runtime error log.'
