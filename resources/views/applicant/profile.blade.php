@extends('layouts.applicant')

@section('title', 'My Profile · '.config('app.name'))

@php
    $tabs = [
        'personal' => 'Personal Information',
        'contact' => 'Contact Information',
        'government' => 'Government and Tax',
        'documents' => 'Documents',
        'application' => 'Application',
    ];

    // The documents the applicant submitted with the application (file names only).
    $documents = [
        'education' => 'Education Transcripts or Diplomas',
        'workHistory' => 'Work History',
        'hiringRequirements' => 'Hiring Requirements',
    ];
@endphp

{{--
    The applicant's profile, filled in by resources/js/applicant-profile.js from their application in the Recruitment
    data (found by the ?ref= in the address; there are no accounts). It is separate from the Employee and HR profiles.
    The profile shows what they submitted on the application form (the 201 file details). The applicant can edit only their
    email and phone number; the rest, and the application details, are HR's.
    Edit swaps the tabs for a form on this page.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="applicant-profile">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <h1 class="page-title">My Profile</h1>

            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('applicant.dashboard') }}"
                data-applicant-link>
                <x-icon name="back" />
                Back to Dashboard
            </a>
        </div>

        <div class="card d-none" data-applicant-missing>
            <div class="employees-empty">
                <strong>No application found</strong>
                Open this page from your application confirmation, or apply for a position.
                <div class="d-flex justify-content-center flex-wrap gap-2 mt-3">
                    <a class="btn btn-primary" href="{{ route('applicant.apply') }}">Apply for a Position</a>
                    <a class="btn btn-outline-secondary" href="{{ route('login') }}">Back to Sign In</a>
                </div>
            </div>
        </div>

        <div class="d-grid gap-4 d-none" data-applicant-content>
            <section class="card employee-form-card">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="profile-avatar" aria-hidden="true" data-applicant-field="initials"></span>
                    <div class="min-w-0 flex-grow-1">
                        <h2 class="fs-4 mb-1" data-applicant-field="name"></h2>
                        <div class="d-flex align-items-center gap-2 flex-wrap text-body-secondary">
                            <span data-applicant-field="role"></span>
                            <span class="status-pill" data-applicant-status-pill></span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" data-applicant-edit-start>Edit</button>
                </div>
            </section>

            <div class="card employee-form-card" data-applicant-view>
                <ul class="nav nav-underline profile-tabs mb-4" role="tablist">
                    @foreach ($tabs as $key => $label)
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link{{ $loop->first ? ' active' : '' }}" id="applicant-{{ $key }}-tab"
                                data-bs-toggle="tab" data-bs-target="#applicant-{{ $key }}" role="tab"
                                aria-controls="applicant-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content">
                    <section class="tab-pane fade show active" id="applicant-personal" role="tabpanel"
                        aria-labelledby="applicant-personal-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                            <div class="col"><dt>Full Name</dt><dd class="mb-0" data-applicant-field="name"></dd></div>
                            <div class="col"><dt>Birthdate</dt><dd class="mb-0" data-applicant-field="birthdate"></dd></div>
                            <div class="col"><dt>Sex</dt><dd class="mb-0" data-applicant-field="sex"></dd></div>
                            <div class="col"><dt>Civil Status</dt><dd class="mb-0" data-applicant-field="civilStatus"></dd></div>
                        </dl>
                    </section>

                    <section class="tab-pane fade" id="applicant-contact" role="tabpanel"
                        aria-labelledby="applicant-contact-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                            <div class="col"><dt>Email</dt><dd class="mb-0 text-break" data-applicant-field="email"></dd></div>
                            <div class="col"><dt>Phone</dt><dd class="mb-0" data-applicant-field="phone"></dd></div>
                            <div class="col-12"><dt>Address</dt><dd class="mb-0" data-applicant-field="address"></dd></div>
                        </dl>
                    </section>

                    <section class="tab-pane fade" id="applicant-government" role="tabpanel"
                        aria-labelledby="applicant-government-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                            <div class="col"><dt>SSS Number</dt><dd class="mb-0 tabular-nums" data-applicant-field="sssNumber"></dd></div>
                            <div class="col"><dt>PhilHealth Number</dt><dd class="mb-0 tabular-nums" data-applicant-field="philhealthNumber"></dd></div>
                            <div class="col"><dt>Pag-IBIG (HDMF) Number</dt><dd class="mb-0 tabular-nums" data-applicant-field="pagibigNumber"></dd></div>
                            <div class="col"><dt>Tax Identification Number (TIN)</dt><dd class="mb-0 tabular-nums" data-applicant-field="tin"></dd></div>
                            <div class="col"><dt>BIR Number</dt><dd class="mb-0 tabular-nums" data-applicant-field="birNumber"></dd></div>
                        </dl>
                    </section>

                    <section class="tab-pane fade" id="applicant-documents" role="tabpanel"
                        aria-labelledby="applicant-documents-tab" tabindex="0">
                        <div class="d-grid gap-3">
                            @foreach ($documents as $key => $label)
                                <div>
                                    <div class="fw-medium mb-1">{{ $label }}</div>
                                    <ul class="list-group document-list" data-applicant-documents="{{ $key }}"></ul>
                                    <p class="small text-body-secondary mb-0" data-applicant-documents-empty="{{ $key }}">No files attached.</p>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="tab-pane fade" id="applicant-application" role="tabpanel"
                        aria-labelledby="applicant-application-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                            <div class="col"><dt>Position</dt><dd class="mb-0" data-applicant-field="jobTitle"></dd></div>
                            <div class="col"><dt>Department</dt><dd class="mb-0" data-applicant-field="department"></dd></div>
                            <div class="col"><dt>Application Date</dt><dd class="mb-0" data-applicant-field="appliedOn"></dd></div>
                            <div class="col"><dt>Status</dt><dd class="mb-0" data-applicant-field="status"></dd></div>
                        </dl>
                    </section>
                </div>
            </div>

            {{-- Edit mode: the editable fields, with the name beside them for context. --}}
            <form class="card employee-form-card d-none" novalidate data-applicant-form>
                <p class="text-body-secondary mb-4">
                    You can update your email and phone number. Your name and application details are managed by HR.
                </p>

                <section class="mb-4">
                    <h2 class="employee-form-section">Personal Information</h2>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="applicant-name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="applicant-name" readonly data-applicant-input="name">
                        </div>

                        <div class="col-md-6">
                            <label for="applicant-email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="applicant-email" name="email" autocomplete="off"
                                aria-describedby="applicant-email-error" required>
                            <div class="invalid-feedback" id="applicant-email-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="applicant-phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="applicant-phone" name="phone" autocomplete="off"
                                placeholder="+63 9XX XXX XXXX" aria-describedby="applicant-phone-error" required>
                            <div class="invalid-feedback" id="applicant-phone-error"></div>
                        </div>
                    </div>
                </section>

                <div class="d-flex justify-content-end flex-wrap gap-2 mt-2">
                    <button type="button" class="btn btn-outline-secondary" data-applicant-edit-cancel>Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
