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
    <body class="min-vh-100">
        {{-- Login and Create Account open in Dark Mode like the rest of the system; this switches to Light Mode. --}}
        <div class="guest-theme-toggle" data-guest-theme>
            <button type="button" class="app-icon-btn" data-theme-toggle>
                <x-icon name="sun" class="theme-when-dark" />
                <x-icon name="moon" class="theme-when-light" />
                <span class="visually-hidden theme-when-dark">Switch to light mode</span>
                <span class="visually-hidden theme-when-light">Switch to dark mode</span>
            </button>
        </div>

        <main>
            @yield('content')
        </main>

        @stack('scripts')
    </body>
</html>
