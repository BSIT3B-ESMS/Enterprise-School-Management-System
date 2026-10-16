@extends('layouts.guest')

@section('title', 'Sign In · '.config('app.name'))

@section('content')
    <div class="row g-0 min-vh-100" data-page="login">
        <section class="col-md-6 login-brand d-flex flex-column align-items-center justify-content-center text-center text-white">
            <div class="login-brand-logo">
                <x-brand-logo />
            </div>

            <h1 class="login-brand-title">Employee Management System</h1>

            <p class="login-brand-tagline">Secure access for HR, managers, and employees.</p>
        </section>

        <section class="col-md-6 d-flex align-items-center justify-content-center px-3 py-5">
            <form class="login-form w-100 d-grid gap-3" method="post" novalidate data-login-form>
                @csrf

                <h2 class="login-form-title mb-1">Welcome Back</h2>

                <x-flash-messages />

                <div>
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" autocomplete="username"
                        placeholder="name@company.com" aria-describedby="email-error" required>
                    <div class="invalid-feedback" id="email-error"></div>
                </div>

                <div>
                    <label for="password" class="form-label">Password</label>
                    <div class="login-password position-relative">
                        <input type="password" class="form-control" id="password" name="password"
                            autocomplete="current-password" placeholder="Enter your password"
                            aria-describedby="password-error" required>
                        <button type="button" class="login-password-toggle" aria-controls="password" data-password-toggle>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <span data-password-toggle-label>Show</span><span class="visually-hidden"> password</span>
                        </button>
                    </div>
                    <div class="invalid-feedback" id="password-error"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember Me</label>
                    </div>

                    <button type="button" class="btn btn-link p-0" data-forgot-password>Forgot Password?</button>
                </div>

                <button type="submit" class="btn btn-primary login-submit">Sign In</button>
            </form>
        </section>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast login-toast" role="status" aria-live="polite" aria-atomic="true" data-login-toast>
            <div class="d-flex">
                <div class="toast-body">Password reset is not available yet.</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>
@endsection
