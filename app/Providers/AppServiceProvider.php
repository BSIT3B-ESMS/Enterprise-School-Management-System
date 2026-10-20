<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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

        // Colleges, offices, job titles, and pay rates: everyone in HR reads them, only the HR Head changes them.
        Gate::define('manage-reference-data', fn (User $user): bool => $user->isHrHead());

        // The SDD's password policy: at least 12 characters and not found in a known breach (which also rejects the
        // common passwords). There are no composition rules.
        Password::defaults(fn (): Password => Password::min(12)->when(
            config('ems.check_breached_passwords'),
            fn (Password $rule): Password => $rule->uncompromised(),
        ));
    }
}
