@extends('layouts.guest')

@section('title', 'Apply for a Position · '.config('app.name'))

{{--
    Application form for anyone, reached from the login page: no account is needed. Options, validation, and saving
    are handled by resources/js/applicant-apply.js, which adds the application to the same Recruitment data the HR
    pages use (as Applied) and then offers a button to open the Applicant dashboard for it.
--}}
@section('content')
    <div class="container py-4 py-md-5" data-page="applicant-apply" data-portal-url="{{ route('applicant.dashboard') }}"
        data-applications-url="{{ route('applicant.applications') }}">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7 d-grid gap-4">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <h1 class="page-title">Apply for a Position</h1>

                    <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('login') }}"
                        data-apply-back>
                        <x-icon name="back" />
                        <span data-apply-back-label>Back to Sign In</span>
                    </a>
                </div>

                <div class="card d-none" data-apply-none>
                    <div class="employees-empty">
                        <strong>No open positions</strong>
                        There is nothing to apply for right now. Please check back later.
                    </div>
                </div>

                <div class="alert alert-warning mb-0 d-none" role="status" data-apply-position-notice>
                    That position is not open for applications. Choose one of the open positions below.
                </div>

                <form class="card employee-form-card" novalidate data-apply-form>
                    <section class="mb-4">
                        <h2 class="employee-form-section">Personal Information</h2>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="apply-name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="apply-name" name="name" maxlength="120"
                                    autocomplete="name" placeholder="e.g. Juan Dela Cruz"
                                    aria-describedby="apply-name-error" required>
                                <div class="invalid-feedback" id="apply-name-error"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="apply-birthdate" class="form-label">Birthdate</label>
                                <input type="date" class="form-control" id="apply-birthdate" name="birthdate"
                                    autocomplete="bday" aria-describedby="apply-birthdate-error">
                                <div class="invalid-feedback" id="apply-birthdate-error"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="apply-sex" class="form-label">Sex</label>
                                <select class="form-select" id="apply-sex" name="sex">
                                    <option>Female</option>
                                    <option>Male</option>
                                    <option>Prefer not to say</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="apply-civil-status" class="form-label">Civil Status</label>
                                <select class="form-select" id="apply-civil-status" name="civilStatus">
                                    <option>Single</option>
                                    <option>Married</option>
                                    <option>Widowed</option>
                                    <option>Separated</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h2 class="employee-form-section">Contact Information</h2>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="apply-email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="apply-email" name="email" autocomplete="email"
                                    placeholder="name@mail.com" aria-describedby="apply-email-error" required>
                                <div class="invalid-feedback" id="apply-email-error"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="apply-phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="apply-phone" name="phone" autocomplete="tel"
                                    placeholder="+63 9XX XXX XXXX" aria-describedby="apply-phone-error" required>
                                <div class="invalid-feedback" id="apply-phone-error"></div>
                            </div>

                            <div class="col-12">
                                <label for="apply-address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="apply-address" name="address" autocomplete="street-address">
                            </div>
                        </div>
                    </section>

                    <section class="mb-4">
                        <h2 class="employee-form-section">Government and Tax</h2>

                        <div class="row g-3">
                            @foreach ([
                                'sssNumber' => ['sss-number', 'SSS Number'],
                                'philhealthNumber' => ['philhealth-number', 'PhilHealth Number'],
                                'pagibigNumber' => ['pagibig-number', 'Pag-IBIG (HDMF) Number'],
                                'tin' => ['tin', 'Tax Identification Number (TIN)'],
                                'birNumber' => ['bir-number', 'BIR Number'],
                            ] as $name => [$id, $label])
                                <div class="col-md-6">
                                    <label for="apply-{{ $id }}" class="form-label">{{ $label }}</label>
                                    <input type="text" class="form-control tabular-nums" id="apply-{{ $id }}" name="{{ $name }}"
                                        inputmode="numeric" autocomplete="off" aria-describedby="apply-{{ $id }}-error">
                                    <div class="invalid-feedback" id="apply-{{ $id }}-error"></div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="mb-4">
                        <h2 class="employee-form-section">Documents</h2>

                        <div class="row g-3">
                            <x-employee-document-field document="education" label="Education Transcripts or Diplomas" />
                            <x-employee-document-field document="workHistory" label="Work History" />
                            <x-employee-document-field document="hiringRequirements" label="Hiring Requirements" />
                        </div>
                    </section>
                    <section class="mb-4">
                        <h2 class="employee-form-section">Position</h2>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="apply-job-title" class="form-label">Position Applied For</label>
                                <select class="form-select" id="apply-job-title" name="jobTitleId"
                                    aria-describedby="apply-job-title-error" required data-apply-positions></select>
                                <div class="invalid-feedback" id="apply-job-title-error"></div>
                            </div>
                        </div>
                    </section>

                    <div class="d-flex justify-content-end flex-wrap gap-2 mt-2">
                        <a class="btn btn-outline-secondary" href="{{ route('login') }}" data-apply-back>Cancel</a>
                        <button type="submit" class="btn btn-primary">Submit Application</button>
                    </div>
                </form>

                <section class="card employee-form-card d-none" role="status" aria-labelledby="apply-success-title" data-apply-success>
                    <h2 class="fs-4 mb-1" id="apply-success-title" tabindex="-1">Application submitted</h2>
                    <p class="text-body-secondary">Thank you, <span data-apply-field="firstName"></span>. HR will review your application.</p>

                    <dl class="row row-cols-1 row-cols-md-2 g-3 mb-4 employee-details">
                        <div class="col"><dt>Position</dt><dd class="mb-0" data-apply-field="jobTitle"></dd></div>
                        <div class="col"><dt>Department</dt><dd class="mb-0" data-apply-field="department"></dd></div>
                        <div class="col"><dt>Application Date</dt><dd class="mb-0" data-apply-field="appliedOn"></dd></div>
                        <div class="col"><dt>Status</dt><dd class="mb-0"><span class="status-pill stage-applied">Applied</span></dd></div>
                    </dl>

                    <div class="d-flex justify-content-end flex-wrap gap-2">
                        <a class="btn btn-outline-secondary" href="{{ route('login') }}">Back to Sign In</a>
                        <a class="btn btn-outline-primary" href="{{ route('applicant.applications') }}" data-apply-applications-link>View My Applications</a>
                        <a class="btn btn-primary" href="{{ route('applicant.dashboard') }}" data-apply-portal-link>Open My Dashboard</a>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
