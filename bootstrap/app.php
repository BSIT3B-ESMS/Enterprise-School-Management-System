<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) return \App\Support\ApiResponse::error('Validation failed', 422, $e->errors());
        });
        $exceptions->render(function (\Throwable $e, $request) {
            if (!$request->is('api/*')) return null;
            $code = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getStatusCode() : 500;
            $message = $code >= 500 ? 'Server error. Check the application log.' : (\Symfony\Component\HttpFoundation\Response::$statusTexts[$code] ?? 'Request failed');
            return \App\Support\ApiResponse::error($message, $code);
        });
    })->create();
