@extends('layouts.guest')

@section('title', 'Sign In · '.config('app.name'))

{{--
    The EMS Login for HR and employees, the page the EMS opens on (/ and /login); resources/js/login.js checks it.
    Applicants log in at the PLP Careers Applicant Portal instead, so applicant credentials are pointed there.
--}}
@section('content')
    <div class="row g-0 min-vh-100">
        <x-login-brand />

        <section class="col-md-6 d-flex align-items-center justify-content-center px-3 py-5">
            <form class="login-form w-100 d-grid gap-3" method="post" novalidate data-login-form data-login-for="staff"
                data-applicant-dashboard-url="{{ route('applicant.dashboard') }}">
                @csrf

                <h2 class="login-form-title mb-1">Welcome Back</h2>

                <x-flash-messages />

                <div class="alert alert-danger mb-0{{ $errors->any() ? '' : ' d-none' }}" role="alert" data-login-error>{{ $errors->first() }}</div>

                <a class="btn btn-outline-primary login-submit d-none" href="{{ route('applicant.portal') }}" data-login-applicant-hint>
                    Go to the Applicant Portal
                </a>

                <div>
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" autocomplete="username"
                        placeholder="name@company.com" value="{{ old('email') }}" aria-describedby="email-error" required>
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

                    <a class="btn btn-link p-0" href="{{ route('password.help') }}">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary login-submit">Login</button>

                {{-- Applicants use PLP Careers and its Applicant Portal, not this login. --}}
                <div class="d-grid gap-2 border-top pt-3 text-center" data-applicant-entry>
                    <span class="text-body-secondary">Looking for a job?</span>
                    <a class="btn btn-outline-primary login-submit d-inline-flex align-items-center justify-content-center"
                        href="{{ route('careers.index') }}">Visit PLP Careers</a>
                </div>
            </form>
        </section>
    </div>
@endsection
