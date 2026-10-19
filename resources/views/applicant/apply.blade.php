@extends('layouts.applicant')

@section('title', 'Apply for a Position · '.config('app.name'))

@php
    // The 201 file's government and tax fields, with the same names, labels, and rules as the HR employee form.
    $governmentFields = [
        'sssNumber' => ['sss-number', 'SSS Number'],
        'philhealthNumber' => ['philhealth-number', 'PhilHealth Number'],
        'pagibigNumber' => ['pagibig-number', 'Pag-IBIG (HDMF) Number'],
        'tin' => ['tin', 'Tax Identification Number (TIN)'],
        'birNumber' => ['bir-number', 'BIR Number'],
    ];
@endphp

{{--
    The job application form for the signed-in applicant. Its sections, field names, labels, and dropdown options follow
    the 201 file (the HR employee form, resources/views/components/employee-form-fields.blade.php), keeping only what
    applies before hiring. resources/js/applicant-apply.js fills in the applicant's profile, keeps the position chosen
    on Open Positions (?position=), validates the fields, and adds the application to the same Recruitment data the
    HR pages use, as Applied and linked to the applicant's account. A position that is closed, or one already applied
    for, cannot be submitted.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="applicant-apply" data-application-url="{{ route('applicant.applications') }}">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Apply for a Position</h1>
                <p class="text-body-secondary mt-1 mb-0">Your profile details are filled in. Check them before you submit.</p>
            </div>

            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('applicant.positions') }}">
                <x-icon name="back" />
                Back to Open Positions
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

        <form class="card employee-form-card" novalidate data-apply-form>
            <section class="mb-4">
                <h2 class="employee-form-section">Personal Information</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="apply-name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="apply-name" readonly aria-describedby="apply-name-hint"
                            data-apply-account="name">
                        <div class="form-text" id="apply-name-hint">From your account.</div>
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
                <h2 class="employee-form-section">Position Applied For</h2>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="apply-job-title" class="form-label">Position</label>
                        <select class="form-select" id="apply-job-title" name="jobTitleId"
                            aria-describedby="apply-job-title-summary apply-job-title-error" required data-apply-positions></select>
                        <div class="form-text" id="apply-job-title-summary" data-apply-position-summary></div>
                        <div class="invalid-feedback" id="apply-job-title-error"></div>
                    </div>

                    <x-employee-document-field document="hiringRequirements" label="Hiring Requirements" />
                </div>
            </section>

            <section class="mb-4">
                <h2 class="employee-form-section">Contact Information</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="apply-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="apply-email" readonly aria-describedby="apply-email-hint"
                            data-apply-account="email">
                        <div class="form-text" id="apply-email-hint">From your account. Change it in your profile.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="apply-phone" class="form-label">Phone</label>
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
                    @foreach ($governmentFields as $name => [$id, $label])
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
                <h2 class="employee-form-section">Education and Work History</h2>

                <div class="row g-3">
                    <x-employee-document-field document="education" label="Education Transcripts or Diplomas" />
                    <x-employee-document-field document="workHistory" label="Work History" />
                </div>
            </section>

            <p class="small text-body-secondary">
                Documents are listed by file name only; files are not uploaded in this demo.
            </p>

            <div class="d-flex justify-content-end flex-wrap gap-2 mt-2">
                <a class="btn btn-outline-secondary" href="{{ route('applicant.positions') }}">Cancel</a>
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
                <a class="btn btn-outline-secondary" href="{{ route('applicant.dashboard') }}">Go to Dashboard</a>
                <a class="btn btn-primary" href="{{ route('applicant.applications') }}" data-apply-application-link>View Application</a>
            </div>
        </section>
    </div>
@endsection
