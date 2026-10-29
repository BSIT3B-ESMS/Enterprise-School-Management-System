<?php

use App\Http\Middleware\AuthenticateApiConsumer;
use App\Http\Middleware\EnforceIdleTimeout;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\RequireOnboarding;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\RequireTwoFactorForHr;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'active' => EnsureAccountIsActive::class,
            'idle' => EnforceIdleTimeout::class,
            'password.fresh' => RequirePasswordChange::class,
            'mfa.hr' => RequireTwoFactorForHr::class,
            'onboarded' => RequireOnboarding::class,
            'api.token' => AuthenticateApiConsumer::class,
        ]);

        // Everything a signed-in staff member reaches: signed in, account active, not idle, past the first-login password change and
        // setup, and (for HR) enrolled in two-factor authentication. The change-password and two-factor setup pages use only
        // the first three, so they stay reachable.
        $middleware->group('account', ['auth', 'auth.session', 'active', 'idle']);
        $middleware->group('staff', ['account', 'password.fresh', 'onboarded', 'mfa.hr']);

        $middleware->redirectUsersTo(fn (Request $request) => $request->user()->homeUrl());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
