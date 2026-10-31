@echo off
setlocal
cd /d "%~dp0"
where php >nul 2>nul
if errorlevel 1 (echo PHP is missing from PATH. Install PHP 8.4 and reopen the terminal. & pause & exit /b 1)
php -r "exit(PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 4 ? 0 : 1);"
if errorlevel 1 (echo This project requires PHP 8.4.x. Run: php -v & pause & exit /b 1)
if not exist vendor\autoload.php (echo Run composer install first. & pause & exit /b 1)
if not exist .env (echo Copy .env.example to .env and configure MySQL first. & pause & exit /b 1)
echo Open http://127.0.0.1:8000 or http://127.0.0.1:8000/kiosk
php artisan serve --host=127.0.0.1 --port=8000
pause
