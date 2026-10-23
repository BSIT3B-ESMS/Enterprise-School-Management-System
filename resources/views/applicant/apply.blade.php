@extends('layouts.applicant')

@section('title', 'Apply for a Position · '.config('app.name'))

@php
    // The same choices as the 201 file (resources/js/validation-rules.js sexOptions and civilStatusOptions).
    $sexOptions = ['Female', 'Male', 'Prefer not to say'];
    $civilStatusOptions = ['Single', 'Married', 'Widowed', 'Separated'];
@endphp

{{--
    The job application form for the signed-in applicant. Its fields and options follow the 201 file (the HR employee
    form), keeping only what applies when applying; the emergency contact and government and tax numbers are
    pre-employment information, asked for only after an offer is accepted. resources/js/applicant-apply.js fills in the
    applicant's account and profile, keeps the position chosen on Open Positions (?position=), keeps what is typed if the
    applicant leaves and comes back in this tab, validates the fields, and adds the application to the same Recruitment
    data the HR pages use, as Applied and linked to the applicant's account. A position that is closed, or one already
    applied for, cannot be submitted. Back returns to the chosen position's details.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="applicant-apply" data-application-url="{{ route('applicant.applications') }}"
        data-positions-url="{{ route('applicant.positions') }}">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <h1 class="page-title">Apply for a Position</h1>

            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('applicant.positions') }}"
                data-apply-back>
                <x-icon name="back" />
                <span data-apply-back-label>Back to Open Positions</span>
            </a>
        </div>

        <div class="card d-none" data-apply-none>
            <div class="employees-empty">
                <strong>No positions to apply for</strong>
                There are no open positions you have not applied for yet. Please check back later.
                <div class="d-flex justify-content-center flex-wrap gap-2 mt-3">
                    <a class="btn btn-outline-secondary" href="{{ route('applicant.applications') }}">View My Applications</a>
                </div>
            </div>
        </div>

        <div class="alert alert-warning mb-0 d-none" role="status" data-apply-position-notice></div>

        <div class="alert alert-info mb-0 d-none" role="status" data-apply-draft-notice></div>

        <form class="card employee-form-card" novalidate data-apply-form>
            <section class="mb-4">
                <h2 class="employee-form-section">Position Applied For</h2>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="apply-job-title" class="form-label is-required">Position</label>
                        <select class="form-select" id="apply-job-title" name="jobTitleId"
                            aria-describedby="apply-job-title-summary apply-job-title-error" required data-apply-positions></select>
                        <div class="form-text" id="apply-job-title-summary" data-apply-position-summary></div>
                        <div class="invalid-feedback" id="apply-job-title-error"></div>
                    </div>

                    <x-employee-document-field document="hiringRequirements" label="Hiring Requirements" />
                </div>
            </section>

            <section class="mb-4">
                <h2 class="employee-form-section">Personal Information <small>Your name is from your account</small></h2>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="apply-last-name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="apply-last-name" readonly data-apply-account="lastName">
                    </div>

                    <div class="col-md-4">
                        <label for="apply-first-name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="apply-first-name" readonly data-apply-account="firstName">
                    </div>

                    <div class="col-md-4">
                        <label for="apply-middle-name" class="form-label">Middle Name</label>
                        <input type="text" class="form-control" id="apply-middle-name" readonly data-apply-account="middleName">
                    </div>

                    <div class="col-md-4">
                        <label for="apply-birthdate" class="form-label is-required">Birthdate</label>
                        <input type="date" class="form-control" id="apply-birthdate" name="birthdate" min="1900-01-01"
                            autocomplete="bday" aria-describedby="apply-birthdate-error" required>
                        <div class="invalid-feedback" id="apply-birthdate-error"></div>
                    </div>

                    <div class="col-md-4">
                        <label for="apply-sex" class="form-label is-required">Sex</label>
                        <select class="form-select" id="apply-sex" name="sex" aria-describedby="apply-sex-error" required>
                            <option value="">Select sex</option>
                            @foreach ($sexOptions as $option)
                                <option>{{ $option }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="apply-sex-error"></div>
                    </div>

                    <div class="col-md-4">
                        <label for="apply-civil-status" class="form-label is-required">Civil Status</label>
                        <select class="form-select" id="apply-civil-status" name="civilStatus" aria-describedby="apply-civil-status-error" required>
                            <option value="">Select civil status</option>
                            @foreach ($civilStatusOptions as $option)
                                <option>{{ $option }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="apply-civil-status-error"></div>
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h2 class="employee-form-section">Contact Information</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="apply-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="apply-email" readonly aria-describedby="apply-email-hint"
                            data-apply-account="email">
                        <div class="form-text" id="apply-email-hint">Change it in My Profile.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="apply-phone" class="form-label is-required">Phone</label>
                        <input type="tel" class="form-control" id="apply-phone" name="phone" autocomplete="tel" inputmode="tel"
                            placeholder="09171234567" data-mask="phone" aria-describedby="apply-phone-hint apply-phone-error" required>
                        <div class="form-text" id="apply-phone-hint">Mobile number: 09XXXXXXXXX or +639XXXXXXXXX</div>
                        <div class="invalid-feedback" id="apply-phone-error"></div>
                    </div>

                    <div class="col-12">
                        <label for="apply-address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="apply-address" name="address" maxlength="200" autocomplete="street-address">
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h2 class="employee-form-section">Education and Work History</h2>

                <div class="row g-3">
                    <x-employee-document-field document="education" label="Education Transcripts or Diplomas" />
                    <x-employee-document-field document="workHistory" label="Work History" />
                </div>
            </section>

            <p class="small text-body-secondary mb-0" data-required-note>
                <span class="text-danger fw-semibold" aria-hidden="true">*</span> Required field. Your emergency contact and
                government numbers are asked for only if you accept a job offer.
            </p>

            <div class="d-flex justify-content-end flex-wrap gap-2 mt-3">
                <a class="btn btn-outline-secondary" href="{{ route('applicant.positions') }}" data-apply-back>Cancel</a>
                <button type="button" class="btn btn-outline-primary" data-apply-save>Save Draft</button>
                <button type="submit" class="btn btn-primary">Submit Application</button>
            </div>
        </form>

        <section class="card employee-form-card d-none" role="status" aria-labelledby="apply-success-title" data-apply-success>
            <h2 class="fs-4 mb-1" id="apply-success-title" tabindex="-1">Application submitted</h2>
            <p class="text-body-secondary">Thank you, <span data-apply-field="firstName"></span>. HR will review your application.</p>

            <dl class="row row-cols-1 row-cols-md-2 g-3 mb-4 employee-details">
                <div class="col"><dt>Position</dt><dd class="mb-0" data-apply-field="jobTitle"></dd></div>
                <div class="col"><dt>College</dt><dd class="mb-0" data-apply-field="department"></dd></div>
                <div class="col"><dt>Application Date</dt><dd class="mb-0" data-apply-field="appliedOn"></dd></div>
                <div class="col"><dt>Status</dt><dd class="mb-0"><span class="status-pill stage-applied">Applied</span></dd></div>
            </dl>

            <div class="d-flex justify-content-end flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('applicant.dashboard') }}">Go to Dashboard</a>
                <a class="btn btn-primary" href="{{ route('applicant.applications') }}" data-apply-application-link>View Application</a>
            </div>
        </section>
    </div>
@endsection
