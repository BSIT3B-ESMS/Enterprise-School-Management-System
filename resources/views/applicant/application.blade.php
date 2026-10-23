@extends('layouts.applicant')

@section('title', 'Application Details · '.config('app.name'))

@php
    // The documents the applicant submitted with the application (file names only).
    $documents = [
        'hiringRequirements' => 'Hiring Requirements',
        'education' => 'Education Transcripts or Diplomas',
        'workHistory' => 'Work History',
    ];

    // The 201 file's government and tax fields, with the same names, labels, and formats as the HR employee form.
    $governmentFields = [
        // name => [id, label, mask, example, format hint]: the format each number is checked against (the BIR number has no fixed one).
        'sssNumber' => ['sss-number', 'SSS Number', 'sss', '00-1234567-8', 'Format: 00-1234567-8 (10 digits)'],
        'philhealthNumber' => ['philhealth-number', 'PhilHealth Number', 'philhealth', '12-123456789-0', 'Format: 12-123456789-0 (12 digits)'],
        'pagibigNumber' => ['pagibig-number', 'Pag-IBIG (HDMF) Number', 'pagibig', '1234-5678-9012', 'Pag-IBIG MID. Format: 1234-5678-9012 (12 digits)'],
        'tin' => ['tin', 'Tax Identification Number (TIN)', 'tin', '123-456-789', 'Format: 123-456-789 (9 digits), or 123-456-789-000 with a branch code'],
        'birNumber' => ['bir-number', 'BIR Number', null, null, null],
    ];
    $relationships = ['Parent', 'Spouse', 'Child', 'Sibling', 'Relative', 'Guardian', 'Friend', 'Other'];
@endphp

{{--
    One of the signed-in applicant's applications, filled in by resources/js/applicant-application.js from the same
    Recruitment data the HR pages use, so its status and schedule follow what HR does. An application that belongs to
    someone else shows as not found. The applicant's own steps are here: once HR issues an offer, only the applicant can
    accept or decline it (each asks for confirmation), and after accepting they give their pre-employment information
    (emergency contact, and government and tax numbers), which HR needs before completing the hiring.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="applicant-application" data-applicant-application="{{ $application }}"
        data-position-url="{{ route('applicant.positions') }}">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Application Details</h1>
                <p class="text-body-secondary mt-1 mb-0">Follow where your application stands.</p>
            </div>

            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('applicant.applications') }}">
                <x-icon name="back" />
                Back to Applications
            </a>
        </div>

        <div class="card d-none" data-applicant-missing>
            <div class="employees-empty">
                <strong>No application found</strong>
                This application does not exist or is not one of yours.
                <div class="d-flex justify-content-center flex-wrap gap-2 mt-3">
                    <a class="btn btn-primary" href="{{ route('applicant.applications') }}">View My Applications</a>
                </div>
            </div>
        </div>

        <div class="d-grid gap-4 d-none" data-applicant-content>
            <section class="card employee-form-card">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div class="min-w-0">
                        <h2 class="fs-4 mb-1" data-applicant-field="jobTitle"></h2>
                        <div class="d-flex align-items-center gap-2 flex-wrap text-body-secondary">
                            <span data-applicant-field="department"></span>
                            <span class="status-pill" data-applicant-status-pill></span>
                        </div>
                    </div>
                    <a class="btn btn-outline-primary" href="{{ route('applicant.positions') }}" data-applicant-position-link>View Position</a>
                </div>

                <p class="mt-3 mb-0" role="status" data-applicant-status-message></p>
            </section>

            <section class="card employee-form-card d-none" aria-labelledby="applicant-offer-title" data-applicant-offer>
                <h2 class="fs-5 mb-1" id="applicant-offer-title">Job Offer</h2>
                <p class="text-body-secondary" data-applicant-offer-status></p>

                <dl class="row row-cols-1 row-cols-sm-3 g-3 mb-3 employee-details">
                    <div class="col"><dt>Monthly Salary</dt><dd class="mb-0 tabular-nums" data-applicant-field="offerSalary"></dd></div>
                    <div class="col"><dt>Start Date</dt><dd class="mb-0" data-applicant-field="offerStartDate"></dd></div>
                    <div class="col"><dt>Your Response</dt><dd class="mb-0" data-applicant-field="offerResponse"></dd></div>
                    <div class="col-12"><dt>Notes from HR</dt><dd class="mb-0" data-applicant-field="offerNotes"></dd></div>
                </dl>

                <div class="d-flex flex-wrap gap-2 d-none" data-applicant-offer-actions>
                    <button type="button" class="btn btn-primary" data-applicant-offer-respond="Accepted">Accept Offer</button>
                    <button type="button" class="btn btn-outline-danger" data-applicant-offer-respond="Declined">Decline Offer</button>
                </div>

                <div class="alert alert-warning mt-3 mb-0 d-none" role="alertdialog" aria-labelledby="applicant-offer-confirm-message"
                    data-applicant-offer-confirm>
                    <p class="mb-2" id="applicant-offer-confirm-message" data-applicant-offer-confirm-message></p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-applicant-offer-confirm-no>Go back</button>
                        <button type="button" class="btn btn-sm btn-primary" data-applicant-offer-confirm-yes></button>
                    </div>
                </div>
            </section>

            <section class="card employee-form-card d-none" aria-labelledby="applicant-pre-employment-title" data-applicant-pre-employment>
                <h2 class="fs-5 mb-1" id="applicant-pre-employment-title">Pre-employment Information</h2>
                <p class="text-body-secondary" data-applicant-pre-employment-status></p>

                <form novalidate data-applicant-pre-employment-form>
                    <h3 class="employee-form-section">Emergency Contact</h3>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="pre-employment-emergency-name" class="form-label is-required">Emergency Contact Name</label>
                            <input type="text" class="form-control" id="pre-employment-emergency-name" name="emergencyContactName" maxlength="120"
                                autocomplete="off" placeholder="e.g. Maria Dela Cruz" aria-describedby="pre-employment-emergency-name-error" required>
                            <div class="invalid-feedback" id="pre-employment-emergency-name-error"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="pre-employment-emergency-number" class="form-label is-required">Emergency Contact Number</label>
                            <input type="tel" class="form-control" id="pre-employment-emergency-number" name="emergencyContactNumber" autocomplete="off"
                                inputmode="tel" placeholder="09171234567" data-mask="phone"
                                aria-describedby="pre-employment-emergency-number-hint pre-employment-emergency-number-error" required>
                            <div class="form-text" id="pre-employment-emergency-number-hint">Mobile number: 09XXXXXXXXX or +639XXXXXXXXX</div>
                            <div class="invalid-feedback" id="pre-employment-emergency-number-error"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="pre-employment-emergency-relationship" class="form-label is-required">Relationship to You</label>
                            <select class="form-select" id="pre-employment-emergency-relationship" name="emergencyContactRelationship"
                                aria-describedby="pre-employment-emergency-relationship-error" required>
                                <option value="">Select relationship</option>
                                @foreach ($relationships as $relationship)
                                    <option>{{ $relationship }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="pre-employment-emergency-relationship-error"></div>
                        </div>
                    </div>

                    <h3 class="employee-form-section">Government and Tax</h3>

                    <div class="row g-3">
                        @foreach ($governmentFields as $name => [$id, $label, $mask, $example, $hint])
                            <div class="col-md-6">
                                <label for="pre-employment-{{ $id }}" class="form-label">{{ $label }}</label>
                                <input type="text" class="form-control tabular-nums" id="pre-employment-{{ $id }}" name="{{ $name }}"
                                    inputmode="numeric" autocomplete="off"
                                    @if ($mask) data-mask="{{ $mask }}" placeholder="{{ $example }}" @endif
                                    aria-describedby="{{ $hint ? 'pre-employment-'.$id.'-hint ' : '' }}pre-employment-{{ $id }}-error">
                                @if ($hint)
                                    <div class="form-text" id="pre-employment-{{ $id }}-hint">{{ $hint }}</div>
                                @endif
                                <div class="invalid-feedback" id="pre-employment-{{ $id }}-error"></div>
                            </div>
                        @endforeach
                    </div>

                    <p class="small text-body-secondary mt-3 mb-0">
                        <span class="text-danger fw-semibold" aria-hidden="true">*</span> Required field. The government
                        numbers are optional here; give the ones you have.
                    </p>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary" data-applicant-pre-employment-save>Submit Information</button>
                    </div>
                </form>
            </section>

            <section class="card dashboard-panel" aria-labelledby="applicant-progress-title">
                <h2 id="applicant-progress-title">Progress</h2>
                <p class="dashboard-panel-subtitle">The stages an application goes through</p>

                <ol class="recruitment-steps mb-0" data-applicant-steps></ol>
            </section>

            <div class="row g-4">
                <div class="col-lg-7">
                    <section class="card dashboard-panel" aria-labelledby="applicant-schedule-title">
                        <h2 id="applicant-schedule-title">Demo Interview and Final Interview</h2>
                        <p class="dashboard-panel-subtitle">Your schedule, as set by HR</p>

                        <ul class="list-group list-group-flush dashboard-list" data-applicant-activities></ul>
                        <p class="text-body-secondary mb-0 d-none" data-applicant-activities-empty>Nothing has been scheduled yet.</p>
                    </section>
                </div>

                <div class="col-lg-5">
                    <section class="card dashboard-panel" aria-labelledby="applicant-job-title">
                        <h2 id="applicant-job-title">Position</h2>
                        <p class="dashboard-panel-subtitle">What you applied for</p>

                        <dl class="row row-cols-1 row-cols-sm-2 g-3 mb-0 employee-details">
                            <div class="col"><dt>Position</dt><dd class="mb-0" data-applicant-field="jobTitle"></dd></div>
                            <div class="col"><dt>College</dt><dd class="mb-0" data-applicant-field="department"></dd></div>
                            <div class="col"><dt>Employment Type</dt><dd class="mb-0" data-applicant-field="employmentType"></dd></div>
                            <div class="col"><dt>Location</dt><dd class="mb-0" data-applicant-field="location"></dd></div>
                            <div class="col"><dt>Application Date</dt><dd class="mb-0" data-applicant-field="appliedOn"></dd></div>
                            <div class="col"><dt>Status</dt><dd class="mb-0" data-applicant-field="status"></dd></div>
                        </dl>
                    </section>
                </div>
            </div>

            <section class="card dashboard-panel" aria-labelledby="applicant-details-title">
                <h2 id="applicant-details-title">Your application</h2>
                <p class="dashboard-panel-subtitle">What you submitted</p>

                <dl class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mb-4 employee-details">
                    <div class="col"><dt>Last Name</dt><dd class="mb-0" data-applicant-field="lastName"></dd></div>
                    <div class="col"><dt>First Name</dt><dd class="mb-0" data-applicant-field="firstName"></dd></div>
                    <div class="col"><dt>Middle Name</dt><dd class="mb-0" data-applicant-field="middleName"></dd></div>
                    <div class="col"><dt>Email</dt><dd class="mb-0 text-break" data-applicant-field="email"></dd></div>
                    <div class="col"><dt>Phone</dt><dd class="mb-0" data-applicant-field="phone"></dd></div>
                    <div class="col"><dt>Birthdate</dt><dd class="mb-0" data-applicant-field="birthdate"></dd></div>
                    <div class="col"><dt>Sex</dt><dd class="mb-0" data-applicant-field="sex"></dd></div>
                    <div class="col"><dt>Civil Status</dt><dd class="mb-0" data-applicant-field="civilStatus"></dd></div>
                    <div class="col-12"><dt>Address</dt><dd class="mb-0" data-applicant-field="address"></dd></div>
                </dl>

                <div class="row row-cols-1 row-cols-md-3 g-3">
                    @foreach ($documents as $key => $label)
                        <div class="col">
                            <div class="fw-medium mb-1">{{ $label }}</div>
                            <ul class="list-group document-list" data-applicant-documents="{{ $key }}"></ul>
                            <p class="small text-body-secondary mb-0" data-applicant-documents-empty="{{ $key }}">No files attached.</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
@endsection
