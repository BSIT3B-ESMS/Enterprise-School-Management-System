$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$toolRoot = 'C:\Users\Mitchell\Documents\Codex\2026-10-30\unde\work\tools'
$php = if (Get-Command php -ErrorAction SilentlyContinue) { (Get-Command php).Source } elseif (Test-Path "$toolRoot\php\php.exe") { "$toolRoot\php\php.exe" } else { throw 'Install PHP 8.2+ and add it to PATH.' }
$env:PATH = (Split-Path $php) + ';' + $env:PATH
if (!(Test-Path vendor/autoload.php)) { throw 'Run composer install first.' }
if (!(Test-Path .env)) { Copy-Item .env.example .env }
$mysqlLocal = (Get-Content .env | Where-Object { $_ -eq 'DB_CONNECTION=mysql' }) -and (Get-Content .env | Where-Object { $_ -eq 'DB_PORT=3307' })
if ($mysqlLocal) {
    $client = [Net.Sockets.TcpClient]::new()
    try { $client.Connect('127.0.0.1',3307); $databaseRunning = $true } catch { $databaseRunning = $false } finally { $client.Dispose() }
    if (!$databaseRunning) {
        $server = "$toolRoot\mariadb-11.4.9-winx64\bin\mariadbd.exe"
        $dataDir = "$toolRoot\mariadb-data"
        if (!(Test-Path $server) -or !(Test-Path $dataDir)) { throw 'Start the configured MariaDB server before launching the application.' }
        Start-Process -FilePath $server -ArgumentList @("--datadir=`"$dataDir`"",'--port=3307','--bind-address=127.0.0.1','--skip-log-bin') -WindowStyle Hidden | Out-Null
        $ready = $false
        for ($attempt=0; $attempt -lt 30; $attempt++) {
            $client = [Net.Sockets.TcpClient]::new()
            try { $client.Connect('127.0.0.1',3307); $ready = $true; break } catch { Start-Sleep -Milliseconds 500 } finally { $client.Dispose() }
        }
        if (!$ready) { throw 'MariaDB did not become ready. Check the log in its data folder.' }
    }
}
& $php scripts/warm-cache.php
if ($LASTEXITCODE -ne 0) { throw 'Provider cache setup failed.' }
if (@(Get-Content .env | Where-Object { $_ -match '^APP_KEY=.+$' }).Count -eq 0) {
    & $php artisan key:generate --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'Application key setup failed.' }
}
& $php artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw 'Database setup failed.' }
& $php artisan serve --host=127.0.0.1 --port=8000 --no-reload