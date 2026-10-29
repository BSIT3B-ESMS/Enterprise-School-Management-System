<?php

namespace App\Providers;

use App\Models\User;
use App\Rules\NotCommonPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Colleges, offices, job titles, and pay rates: everyone in HR reads them, only the HR Admin changes them.
        Gate::define('manage-reference-data', fn (User $user): bool => $user->isHrAdmin());

        // The API allows each consumer (or, before a token is sent, each address) 60 requests a minute.
        RateLimiter::for('ems-api', fn (Request $request): Limit => Limit::perMinute(60)->by($request->bearerToken() ? hash('sha256', $request->bearerToken()) : $request->ip()));

        // At least 8 characters with an uppercase letter, a lowercase letter, and a number, nothing easy to guess, and
        // (when enabled) nothing found in a known breach.
        Password::defaults(fn (): Password => Password::min(8)->mixedCase()->numbers()->rules([new NotCommonPassword])->when(
            config('ems.check_breached_passwords'),
            fn (Password $rule): Password => $rule->uncompromised(),
        ));
    }
}
