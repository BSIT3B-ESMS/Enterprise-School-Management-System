<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append:[\App\Http\Middleware\RequireActiveAccount::class,\App\Http\Middleware\RestrictProfessorRoutes::class]);
        $middleware->alias(['account.active'=>\App\Http\Middleware\RequireActiveAccount::class,'abilities'=>\Laravel\Sanctum\Http\Middleware\CheckAbilities::class,'ability'=>\Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,'role'=>\App\Http\Middleware\RequireRole::class,'password.changed'=>\App\Http\Middleware\RequirePasswordChange::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})->create();
// Recognize Windows drive paths for explicitly configured cache locations.
if (PHP_OS_FAMILY === 'Windows') { $app->addAbsoluteCachePathPrefix(substr(base_path(), 0, 3)); }
return $app;
