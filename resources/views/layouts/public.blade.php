<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name'))</title>

        @vite(['resources/scss/app.scss', 'resources/js/app.js'])

        @stack('styles')
    </head>
    <body>
        {{--
            The public Careers website, open to anyone without an account. The header offers the shared Login page and
            Create Account (which asks for the Terms and Conditions first), or the dashboard when an applicant is
            already signed in this session (resources/js/applicant-positions.js).
        --}}
        <header class="app-header public-header">
            <a class="app-brand" href="{{ route('careers.index') }}">
                <span class="app-brand-logo"><x-brand-logo /></span>
                <span class="app-brand-name">PLP Careers</span>
            </a>

            <div class="d-flex align-items-center gap-2">
                <button type="button" class="app-icon-btn" data-theme-toggle>
                    <x-icon name="sun" class="theme-when-dark" />
                    <x-icon name="moon" class="theme-when-light" />
                    <span class="visually-hidden theme-when-dark">Switch to light mode</span>
                    <span class="visually-hidden theme-when-light">Switch to dark mode</span>
                </button>

                <div class="d-flex align-items-center gap-2" data-public-signed-out>
                    <a class="btn btn-sm btn-outline-light" href="{{ route('login') }}">Login</a>
                    <a class="btn btn-sm btn-light" href="{{ route('applicant.register') }}" data-terms-continue>Create Account</a>
                </div>

                <a class="btn btn-sm btn-light d-none" href="{{ route('applicant.dashboard') }}" data-public-signed-in>My Dashboard</a>
            </div>
        </header>

        <main class="app-main">
            <div class="app-content">
                @yield('content')
            </div>
        </main>

        <x-applicant-terms-modal />

        @stack('scripts')
    </body>
</html>
