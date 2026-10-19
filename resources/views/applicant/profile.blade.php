@extends('layouts.applicant')

@section('title', 'My Profile · '.config('app.name'))

@php
    $tabs = [
        'personal' => 'Personal Information',
        'contact' => 'Contact Information',
        'government' => 'Government and Tax',
        'documents' => 'Documents',
    ];

    // The 201 file's government and tax fields, with the same names, labels, and rules as the HR employee form.
    $governmentFields = [
        // name => [id, label, mask, example, format hint]: the format each number is checked against (the BIR number has no fixed one).
        'sssNumber' => ['sss-number', 'SSS Number', 'sss', '00-1234567-8', 'Format: 00-1234567-8 (10 digits)'],
        'philhealthNumber' => ['philhealth-number', 'PhilHealth Number', 'philhealth', '12-123456789-0', 'Format: 12-123456789-0 (12 digits)'],
        'pagibigNumber' => ['pagibig-number', 'Pag-IBIG (HDMF) Number', 'pagibig', '1234-5678-9012', 'Pag-IBIG MID. Format: 1234-5678-9012 (12 digits)'],
        'tin' => ['tin', 'Tax Identification Number (TIN)', 'tin', '123-456-789', 'Format: 123-456-789 (9 digits), or 123-456-789-000 with a branch code'],
        'birNumber' => ['bir-number', 'BIR Number', null, null, null],
    ];

    // The documents from the applicant's latest application (file names only).
    $documents = [
        'hiringRequirements' => 'Hiring Requirements',
        'education' => 'Education Transcripts or Diplomas',
        'workHistory' => 'Work History',
    ];
@endphp

{{--
    The signed-in applicant's profile, filled in by resources/js/applicant-profile.js from their account. It is separate
    from the Employee and HR profiles. The details follow the 201 file and fill in the next job application. Edit swaps
    the tabs for a form on this page: everything but the name can be changed; the email address and phone number also
    change on submitted applications, so HR can reach the applicant.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="applicant-profile">
        <h1 class="page-title">My Profile</h1>

        <section class="card employee-form-card">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="profile-avatar" aria-hidden="true" data-applicant-field="initials"></span>
                <div class="min-w-0 flex-grow-1">
                    <h2 class="fs-4 mb-1" data-applicant-field="name"></h2>
                    <div class="d-flex align-items-center gap-2 flex-wrap text-body-secondary">
                        <span class="text-break" data-applicant-field="email"></span>
                        <span class="ems-chip">Applicant</span>
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
                        <div class="col"><dt>Account Created</dt><dd class="mb-0" data-applicant-field="createdOn"></dd></div>
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
                        @foreach ($governmentFields as $name => [$id, $label])
                            <div class="col"><dt>{{ $label }}</dt><dd class="mb-0 tabular-nums" data-applicant-field="{{ $name }}"></dd></div>
                        @endforeach
                    </dl>
                </section>

                <section class="tab-pane fade" id="applicant-documents" role="tabpanel"
                    aria-labelledby="applicant-documents-tab" tabindex="0">
                    <p class="small text-body-secondary">From your latest application. Add documents when you apply.</p>
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
            </div>
        </div>

        {{-- Edit mode: the 201 file details, prefilled. The name is shown for context but cannot be changed. --}}
        <form class="card employee-form-card d-none" novalidate data-applicant-form>
            <section class="mb-4">
                <h2 class="employee-form-section">Personal Information</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="applicant-name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="applicant-name" readonly aria-describedby="applicant-name-hint"
                            data-applicant-input="name">
                        <div class="form-text" id="applicant-name-hint">Your name is used on your applications and cannot be changed here.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="applicant-birthdate" class="form-label">Birthdate</label>
                        <input type="date" class="form-control" id="applicant-birthdate" name="birthdate"
                            autocomplete="bday" aria-describedby="applicant-birthdate-error">
                        <div class="invalid-feedback" id="applicant-birthdate-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="applicant-sex" class="form-label">Sex</label>
                        <select class="form-select" id="applicant-sex" name="sex">
                            <option value="">Not provided</option>
                            <option>Female</option>
                            <option>Male</option>
                            <option>Prefer not to say</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="applicant-civil-status" class="form-label">Civil Status</label>
                        <select class="form-select" id="applicant-civil-status" name="civilStatus">
                            <option value="">Not provided</option>
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
                        <label for="applicant-email" class="form-label is-required">Email</label>
                        <input type="email" class="form-control" id="applicant-email" name="email" autocomplete="email"
                            aria-describedby="applicant-email-error" required>
                        <div class="invalid-feedback" id="applicant-email-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="applicant-phone" class="form-label is-required">Phone</label>
                        <input type="tel" class="form-control" id="applicant-phone" name="phone" autocomplete="tel" inputmode="tel"
                            placeholder="09171234567" data-mask="phone" aria-describedby="applicant-phone-hint applicant-phone-error" required>
                        <div class="form-text" id="applicant-phone-hint">Mobile number: 09XXXXXXXXX or +639XXXXXXXXX</div>
                        <div class="invalid-feedback" id="applicant-phone-error"></div>
                    </div>

                    <div class="col-12">
                        <label for="applicant-address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="applicant-address" name="address" autocomplete="street-address">
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h2 class="employee-form-section">Government and Tax</h2>

                <div class="row g-3">
                        @foreach ($governmentFields as $name => [$id, $label, $mask, $example, $hint])
                        <div class="col-md-6">
                            <label for="applicant-{{ $id }}" class="form-label">{{ $label }}</label>
                            <input type="text" class="form-control tabular-nums" id="applicant-{{ $id }}" name="{{ $name }}"
                                inputmode="numeric" autocomplete="off"
                                @if ($mask) data-mask="{{ $mask }}" placeholder="{{ $example }}" @endif
                                aria-describedby="{{ $hint ? 'applicant-'.$id.'-hint ' : '' }}applicant-{{ $id }}-error">
                            @if ($hint)
                                <div class="form-text" id="applicant-{{ $id }}-hint">{{ $hint }}</div>
                            @endif
                            <div class="invalid-feedback" id="applicant-{{ $id }}-error"></div>
                        </div>
                    @endforeach
                </div>
            </section>

            <p class="small text-body-secondary mb-2">
                <span class="text-danger fw-semibold" aria-hidden="true">*</span> Required field
            </p>

            <p class="small text-body-secondary">
                Your email and phone number also change on the applications you have submitted, so HR can reach you.
                The other details are used for your next application.
            </p>

            <div class="d-flex justify-content-end flex-wrap gap-2 mt-2">
                <button type="button" class="btn btn-outline-secondary" data-applicant-edit-cancel>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
@endsection
