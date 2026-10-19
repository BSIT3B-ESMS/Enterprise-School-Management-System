@extends('layouts.public')

@section('title', 'Applicant Portal · '.config('app.name'))

{{--
    The public Applicant Portal that Apply on the Careers website opens, with the chosen position kept in ?position=
    (filled in by resources/js/applicant-auth.js). Create Account shows the Terms and Conditions first (only then, not
    on entering the portal); accepting opens the registration form and declining returns to Open Positions with the
    position still selected. Login opens the shared Login page.
--}}
@section('content')
    <div class="row justify-content-center" data-page="applicant-portal" data-careers-url="{{ route('careers.index') }}"
        data-register-url="{{ route('applicant.register') }}" data-login-url="{{ route('login') }}">
        <div class="col-lg-8 col-xl-7 d-grid gap-4">
            <div>
                <h1 class="page-title">Applicant Portal</h1>
                <p class="text-body-secondary mt-1 mb-0">Create an account or log in to apply and follow your applications.</p>
            </div>

            <div class="alert alert-secondary mb-0 d-none" role="status" data-portal-position>
                You are applying for <strong data-portal-position-name></strong>. It stays selected while you continue.
            </div>

            <div class="alert alert-warning mb-0 d-none" role="status" data-portal-position-closed>
                That position is not open for applications. Choose one from the open positions.
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3">
                <div class="col">
                    <section class="card employee-form-card h-100" aria-labelledby="portal-create-title">
                        <h2 class="fs-5" id="portal-create-title">New applicant</h2>
                        <p class="text-body-secondary">Create an account to apply for a position. You will read and accept the Terms and Conditions first.</p>
                        <a class="btn btn-primary mt-auto" href="{{ route('applicant.register') }}" data-terms-continue
                            data-portal-create>Create Account</a>
                    </section>
                </div>

                <div class="col">
                    <section class="card employee-form-card h-100" aria-labelledby="portal-login-title">
                        <h2 class="fs-5" id="portal-login-title">Already have an account?</h2>
                        <p class="text-body-secondary">Log in to continue your application and follow your applications.</p>
                        <a class="btn btn-outline-primary mt-auto" href="{{ route('login') }}" data-portal-login>Login</a>
                    </section>
                </div>
            </div>

            <div>
                <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('careers.index') }}"
                    data-portal-back>
                    <x-icon name="back" />
                    Back to Open Positions
                </a>
            </div>
        </div>
    </div>
@endsection
