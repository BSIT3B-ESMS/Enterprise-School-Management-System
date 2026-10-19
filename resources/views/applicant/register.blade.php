@extends('layouts.guest')

@section('title', 'Create an Applicant Account · '.config('app.name'))

{{--
    Create Account for new applicants, reached from the public Careers website, beside the same brand panel as the
    shared Login page. The Terms and Conditions open first and the form is not shown until they are accepted; declining
    returns to the previous page (resources/js/applicant-terms.js). resources/js/applicant-auth.js validates the fields, refuses an email address that
    already has a mock account, creates the account, signs it in for this browser tab's session, and then opens the
    Applicant Dashboard, keeping the position in ?position= (from the Careers website).
--}}
@section('content')
    <div class="row g-0 min-vh-100" data-page="applicant-register"
        data-back-url="{{ route('careers.index') }}" data-dashboard-url="{{ route('applicant.dashboard') }}">
        <x-login-brand tagline="Create an account to apply for open positions." />

        <section class="col-md-6 d-flex align-items-center justify-content-center px-3 py-5">
            <form class="login-form w-100 d-grid gap-3 d-none" novalidate data-applicant-register-form>
                <div>
                    <h2 class="login-form-title mb-1">Create an Applicant Account</h2>
                    <p class="text-body-secondary mb-0">You will use it to apply and to follow your applications.</p>
                </div>

                <x-applicant-auth-notices />

                <div class="alert alert-danger mb-0 d-none" role="alert" data-applicant-auth-error></div>

                <div class="d-grid gap-3">
                    <div>
                        <label for="applicant-register-name" class="form-label is-required">Full Name</label>
                        <input type="text" class="form-control" id="applicant-register-name" name="name" maxlength="120"
                            autocomplete="name" placeholder="e.g. Juan Dela Cruz" aria-describedby="applicant-register-name-error" required>
                        <div class="invalid-feedback" id="applicant-register-name-error"></div>
                    </div>

                    <div>
                        <label for="applicant-register-email" class="form-label is-required">Email</label>
                        <input type="email" class="form-control" id="applicant-register-email" name="email" autocomplete="email"
                            placeholder="name@mail.com" aria-describedby="applicant-register-email-error" required>
                        <div class="invalid-feedback" id="applicant-register-email-error"></div>
                    </div>

                    <div>
                        <x-password-field id="applicant-register-password" name="password" label="Password" autocomplete="new-password" marked
                            hint="At least 8 characters, with an uppercase letter, a lowercase letter, and a number." />
                    </div>

                    <div>
                        <x-password-field id="applicant-register-confirm-password" name="confirmPassword" label="Confirm Password"
                            autocomplete="new-password" marked />
                    </div>

                    <p class="small text-body-secondary mb-0" data-required-note>
                        <span class="text-danger fw-semibold" aria-hidden="true">*</span> Required field
                    </p>

                    <p class="small text-body-secondary mb-0">
                        This is a demo account kept in this browser only. Do not use a password you use anywhere else.
                    </p>

                    <button type="submit" class="btn btn-primary login-submit">Create Account</button>
                </div>

                <div class="d-grid gap-2 border-top pt-3 text-center">
                    <span class="text-body-secondary">
                        Already have an account? <a href="{{ route('login') }}" data-login-here>Login here</a>
                    </span>
                    <a class="small" href="{{ route('careers.index') }}">Back to Careers</a>
                </div>
            </form>
        </section>
    </div>

    <x-applicant-terms-modal />
@endsection
