@extends('layouts.applicant')

@section('title', 'Apply for a Position · '.config('app.name'))

@php
    // The 201 file's government and tax fields, with the same names, labels, and rules as the HR employee form.
    $governmentFields = [
        // name => [id, label, mask, example, format hint]: the format each number is checked against (the BIR number has no fixed one).
        'sssNumber' => ['sss-number', 'SSS Number', 'sss', '00-1234567-8', 'Format: 00-1234567-8 (10 digits)'],
        'philhealthNumber' => ['philhealth-number', 'PhilHealth Number', 'philhealth', '12-123456789-0', 'Format: 12-123456789-0 (12 digits)'],
        'pagibigNumber' => ['pagibig-number', 'Pag-IBIG (HDMF) Number', 'pagibig', '1234-5678-9012', 'Pag-IBIG MID. Format: 1234-5678-9012 (12 digits)'],
        'tin' => ['tin', 'Tax Identification Number (TIN)', 'tin', '123-456-789', 'Format: 123-456-789 (9 digits), or 123-456-789-000 with a branch code'],
        'birNumber' => ['bir-number', 'BIR Number', null, null, null],
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

        <div class="alert alert-info mb-0 d-none" role="status" data-apply-draft-notice></div>

        <form class="card employee-form-card" novalidate data-apply-form>
            <section class="mb-4">
                <h2 class="employee-form-section">Personal Information</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="apply-name" class="form-label is-required">Full Name</label>
                        <input type="text" class="form-control" id="apply-name" readonly aria-required="true" aria-describedby="apply-name-hint"
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
                <h2 class="employee-form-section">Contact Information</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="apply-email" class="form-label is-required">Email</label>
                        <input type="email" class="form-control" id="apply-email" readonly aria-required="true" aria-describedby="apply-email-hint"
                            data-apply-account="email">
                        <div class="form-text" id="apply-email-hint">From your account. Change it in your profile.</div>
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
                        <input type="text" class="form-control" id="apply-address" name="address" autocomplete="street-address">
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h2 class="employee-form-section">Emergency Contact</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="apply-emergency-name" class="form-label is-required">Emergency Contact Name</label>
                        <input type="text" class="form-control" id="apply-emergency-name" name="emergencyContactName" maxlength="120"
                            autocomplete="off" placeholder="e.g. Maria Dela Cruz" aria-describedby="apply-emergency-name-error" required>
                        <div class="invalid-feedback" id="apply-emergency-name-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="apply-emergency-number" class="form-label is-required">Emergency Contact Number</label>
                        <input type="tel" class="form-control" id="apply-emergency-number" name="emergencyContactNumber" autocomplete="off"
                            inputmode="tel" placeholder="09171234567" data-mask="phone"
                            aria-describedby="apply-emergency-number-hint apply-emergency-number-error" required>
                        <div class="form-text" id="apply-emergency-number-hint">Mobile number: 09XXXXXXXXX or +639XXXXXXXXX</div>
                        <div class="invalid-feedback" id="apply-emergency-number-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="apply-emergency-relationship" class="form-label is-required">Relationship to Applicant</label>
                        <select class="form-select" id="apply-emergency-relationship" name="emergencyContactRelationship"
                            aria-describedby="apply-emergency-relationship-error" required>
                            <option value="">Select relationship</option>
                            @foreach (['Parent', 'Spouse', 'Child', 'Sibling', 'Relative', 'Guardian', 'Friend', 'Other'] as $relationship)
                                <option>{{ $relationship }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="apply-emergency-relationship-error"></div>
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h2 class="employee-form-section">Government and Tax</h2>

                <div class="row g-3">
                    @foreach ($governmentFields as $name => [$id, $label, $mask, $example, $hint])
                        <div class="col-md-6">
                            <label for="apply-{{ $id }}" class="form-label">{{ $label }}</label>
                            <input type="text" class="form-control tabular-nums" id="apply-{{ $id }}" name="{{ $name }}"
                                inputmode="numeric" autocomplete="off"
                                @if ($mask) data-mask="{{ $mask }}" placeholder="{{ $example }}" @endif
                                aria-describedby="{{ $hint ? 'apply-'.$id.'-hint ' : '' }}apply-{{ $id }}-error">
                            @if ($hint)
                                <div class="form-text" id="apply-{{ $id }}-hint">{{ $hint }}</div>
                            @endif
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

            <p class="small text-body-secondary mb-0" data-required-note>
                <span class="text-danger fw-semibold" aria-hidden="true">*</span> Required field. The other fields are optional.
            </p>

            <div class="d-flex justify-content-end flex-wrap gap-2 mt-2">
                <a class="btn btn-outline-secondary" href="{{ route('applicant.positions') }}">Cancel</a>
                <button type="button" class="btn btn-outline-primary" data-apply-save>Save Draft</button>
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
