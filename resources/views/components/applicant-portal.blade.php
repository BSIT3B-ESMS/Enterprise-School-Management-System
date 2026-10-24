@props(['initial' => 'login'])

@php
    // The same choices as the 201 file (resources/js/validation-rules.js sexOptions and civilStatusOptions).
    $sexOptions = ['Female', 'Male', 'Prefer not to say'];
    $civilStatusOptions = ['Single', 'Married', 'Widowed', 'Separated'];
@endphp

{{--
    The PLP Careers Applicant Portal: the brand panel on the left and one form on the right, Log In by default or Create
    Account (apart from the EMS Login that HR and employees use). Only one form shows at a time; switching keeps what was
    typed in each. resources/js/login.js checks the Log In form, and resources/js/applicant-auth.js switches the forms,
    checks Create Account, and shows the Terms and Conditions before Create Account opens (declining stays on, or returns
    to, Log In or the previous page). `initial="register"` opens on Create Account (the Create Account address).
--}}
<div class="row g-0 min-vh-100" data-page="applicant-portal" data-initial-form="{{ $initial }}"
    data-careers-url="{{ route('careers.index') }}" data-portal-url="{{ route('applicant.portal') }}"
    data-dashboard-url="{{ route('applicant.dashboard') }}">
    <x-login-brand title="PLP Careers" tagline="Applicant Portal: log in or create an account to apply for open positions." />

    <section class="col-md-6 d-flex align-items-center justify-content-center px-3 py-4">
        <div class="w-100 d-grid gap-3 login-form login-form-wide">
            <x-applicant-auth-notices />

            <div class="alert alert-warning mb-0 d-none" role="status" data-portal-position-closed>
                That position is not open for applications. Choose one from the open positions.
            </div>

            {{-- Log In --}}
            <form class="d-grid gap-3{{ $initial === 'login' ? '' : ' d-none' }}" novalidate data-login-form data-login-for="applicant"
                data-applicant-dashboard-url="{{ route('applicant.dashboard') }}" data-portal-form="login">
                <h2 class="login-form-title mb-0">Applicant Login</h2>

                <div class="alert alert-danger mb-0 d-none" role="alert" data-login-error></div>

                <div>
                    <label for="applicant-login-email" class="form-label is-required">Email</label>
                    <input type="email" class="form-control" id="applicant-login-email" name="email" autocomplete="username"
                        placeholder="name@mail.com" aria-describedby="applicant-login-email-error" required>
                    <div class="invalid-feedback" id="applicant-login-email-error"></div>
                </div>

                <div>
                    <x-password-field id="applicant-login-password" name="password" label="Password"
                        autocomplete="current-password" marked />
                </div>

                <button type="submit" class="btn btn-primary login-submit">Log In</button>

                <div class="d-grid gap-2 border-top pt-3 text-center">
                    <span class="text-body-secondary">
                        New applicant? <a href="{{ route('applicant.register') }}" data-portal-switch="register">Create Account</a>
                    </span>
                    <a class="small" href="{{ route('careers.index') }}" data-portal-back>Back to Careers</a>
                </div>
            </form>

            {{-- Create Account: not shown until the Terms and Conditions are accepted. --}}
            <form class="d-grid gap-3 d-none" novalidate data-applicant-register-form data-portal-form="register">
                <div>
                    <h2 class="login-form-title mb-1">Create an Applicant Account</h2>
                    <p class="text-body-secondary mb-0">You will use it to apply and to follow your applications.</p>
                </div>

                <div class="alert alert-danger mb-0 d-none" role="alert" data-applicant-auth-error></div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="applicant-register-last-name" class="form-label is-required">Last Name</label>
                        <input type="text" class="form-control" id="applicant-register-last-name" name="lastName" maxlength="60"
                            autocomplete="family-name" placeholder="e.g. Dela Cruz" aria-describedby="applicant-register-last-name-error" required>
                        <div class="invalid-feedback" id="applicant-register-last-name-error"></div>
                    </div>

                    <div class="col-sm-6">
                        <label for="applicant-register-first-name" class="form-label is-required">First Name</label>
                        <input type="text" class="form-control" id="applicant-register-first-name" name="firstName" maxlength="60"
                            autocomplete="given-name" placeholder="e.g. Juan" aria-describedby="applicant-register-first-name-error" required>
                        <div class="invalid-feedback" id="applicant-register-first-name-error"></div>
                    </div>

                    <div class="col-sm-6">
                        <label for="applicant-register-middle-name" class="form-label">Middle Name</label>
                        <input type="text" class="form-control" id="applicant-register-middle-name" name="middleName" maxlength="60"
                            autocomplete="additional-name" aria-describedby="applicant-register-middle-name-error">
                        <div class="invalid-feedback" id="applicant-register-middle-name-error"></div>
                    </div>

                    <div class="col-sm-6">
                        <label for="applicant-register-birthdate" class="form-label is-required">Birthdate</label>
                        <input type="date" class="form-control" id="applicant-register-birthdate" name="birthdate" autocomplete="bday"
                            min="1900-01-01" aria-describedby="applicant-register-birthdate-error" required>
                        <div class="invalid-feedback" id="applicant-register-birthdate-error"></div>
                    </div>

                    <div class="col-sm-6">
                        <label for="applicant-register-sex" class="form-label is-required">Sex</label>
                        <select class="form-select" id="applicant-register-sex" name="sex" aria-describedby="applicant-register-sex-error" required>
                            <option value="">Select sex</option>
                            @foreach ($sexOptions as $option)
                                <option>{{ $option }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="applicant-register-sex-error"></div>
                    </div>

                    <div class="col-sm-6">
                        <label for="applicant-register-civil-status" class="form-label is-required">Civil Status</label>
                        <select class="form-select" id="applicant-register-civil-status" name="civilStatus"
                            aria-describedby="applicant-register-civil-status-error" required>
                            <option value="">Select civil status</option>
                            @foreach ($civilStatusOptions as $option)
                                <option>{{ $option }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="applicant-register-civil-status-error"></div>
                    </div>

                    <div class="col-12">
                        <label for="applicant-register-email" class="form-label is-required">Email</label>
                        <input type="email" class="form-control" id="applicant-register-email" name="email" autocomplete="email"
                            placeholder="name@mail.com" aria-describedby="applicant-register-email-error" required>
                        <div class="invalid-feedback" id="applicant-register-email-error"></div>
                    </div>

                    <div class="col-sm-6">
                        <x-password-field id="applicant-register-password" name="password" label="Password" autocomplete="new-password" marked />
                    </div>

                    <div class="col-sm-6">
                        <x-password-field id="applicant-register-confirm-password" name="confirmPassword" label="Confirm Password"
                            autocomplete="new-password" marked />
                    </div>
                </div>

                <p class="small text-body-secondary mb-0">
                    <span class="text-danger fw-semibold" aria-hidden="true">*</span> Required. Password: at least 8 characters, with an
                    uppercase letter, a lowercase letter, and a number. This is a demo account kept in this browser only.
                </p>

                <button type="submit" class="btn btn-primary login-submit">Create Account</button>

                <div class="d-grid gap-2 border-top pt-3 text-center">
                    <span class="text-body-secondary">
                        Already have an account? <a href="{{ route('applicant.portal') }}" data-portal-switch="login">Log In</a>
                    </span>
                    <a class="small" href="{{ route('careers.index') }}" data-portal-back>Back to Careers</a>
                </div>
            </form>
        </div>
    </section>
</div>

<x-applicant-terms-modal />
