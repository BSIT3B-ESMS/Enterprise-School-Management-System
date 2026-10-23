@extends('layouts.employee')

@section('title', 'My Profile · '.config('app.name'))

@php
    $tabs = [
        'personal' => 'Personal Information',
        'employment' => 'Employment Information',
        'contact' => 'Contact Information',
        'government' => 'Government and Tax',
    ];
@endphp

{{--
    The signed-in employee's profile (201 file), filled in by resources/js/portal-profile.js from the mock data.
    Same layout as the HR employee profile, but the employee can edit only civil status, phone, and address;
    the rest is HR-controlled and read-only. Edit swaps the tabs for a form on this page.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="portal-profile">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <h1 class="page-title">My Profile</h1>

            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('employee.dashboard') }}">
                <x-icon name="back" />
                Back to Dashboard
            </a>
        </div>

        <div class="card d-none" data-portal-missing>
            <div class="employees-empty">
                <strong>Employee record not found</strong>
                Your record may have been removed. Contact HR for help.
            </div>
        </div>

        <div class="d-grid gap-4" data-portal-content>
            <section class="card employee-form-card">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="profile-avatar" aria-hidden="true" data-portal-field="initials"></span>
                    <div class="min-w-0 flex-grow-1">
                        <h2 class="fs-4 mb-1" data-portal-field="name"></h2>
                        <div class="d-flex align-items-center gap-2 flex-wrap text-body-secondary">
                            <span class="ems-chip tabular-nums" data-portal-field="id"></span>
                            <span data-portal-field="role"></span>
                            <span class="status-pill" data-portal-status-pill></span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" data-portal-edit-start>Edit</button>
                </div>
            </section>

            <div class="card employee-form-card" data-portal-view>
                <ul class="nav nav-underline profile-tabs mb-4" role="tablist">
                    @foreach ($tabs as $key => $label)
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link{{ $loop->first ? ' active' : '' }}" id="portal-{{ $key }}-tab"
                                data-bs-toggle="tab" data-bs-target="#portal-{{ $key }}" role="tab"
                                aria-controls="portal-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content">
                    <section class="tab-pane fade show active" id="portal-personal" role="tabpanel"
                        aria-labelledby="portal-personal-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                            <div class="col"><dt>Full Name</dt><dd class="mb-0" data-portal-field="name"></dd></div>
                            <div class="col"><dt>Employee ID</dt><dd class="mb-0 tabular-nums" data-portal-field="id"></dd></div>
                            <div class="col"><dt>Birthdate</dt><dd class="mb-0" data-portal-field="birthdate"></dd></div>
                            <div class="col"><dt>Sex</dt><dd class="mb-0" data-portal-field="sex"></dd></div>
                            <div class="col"><dt>Civil Status</dt><dd class="mb-0" data-portal-field="civilStatus"></dd></div>
                        </dl>
                    </section>

                    <section class="tab-pane fade" id="portal-employment" role="tabpanel"
                        aria-labelledby="portal-employment-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                            <div class="col"><dt>College</dt><dd class="mb-0" data-portal-field="department"></dd></div>
                            <div class="col"><dt>Job Title</dt><dd class="mb-0" data-portal-field="jobTitle"></dd></div>
                            <div class="col"><dt>Date Hired</dt><dd class="mb-0" data-portal-field="hiredOn"></dd></div>
                            <div class="col"><dt>Employment Status</dt><dd class="mb-0" data-portal-field="status"></dd></div>
                            {{-- From the job title HR manages (read-only). Remaining credits count Approved leave only. --}}
                            <div class="col"><dt>Base Salary (monthly)</dt><dd class="mb-0 tabular-nums" data-portal-field="baseSalary"></dd></div>
                            <div class="col"><dt>Leave Credits</dt><dd class="mb-0" data-portal-field="leaveCredits"></dd></div>
                            <div class="col"><dt>Leave Credits Remaining</dt><dd class="mb-0" data-portal-field="leaveRemaining"></dd></div>
                        </dl>
                    </section>

                    <section class="tab-pane fade" id="portal-contact" role="tabpanel"
                        aria-labelledby="portal-contact-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                            <div class="col"><dt>Email</dt><dd class="mb-0 text-break" data-portal-field="email"></dd></div>
                            <div class="col"><dt>Phone</dt><dd class="mb-0" data-portal-field="phone"></dd></div>
                            <div class="col-12"><dt>Address</dt><dd class="mb-0" data-portal-field="address"></dd></div>
                        </dl>
                    </section>

                    <section class="tab-pane fade" id="portal-government" role="tabpanel"
                        aria-labelledby="portal-government-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                            <div class="col"><dt>SSS Number</dt><dd class="mb-0 tabular-nums" data-portal-field="sssNumber"></dd></div>
                            <div class="col"><dt>PhilHealth Number</dt><dd class="mb-0 tabular-nums" data-portal-field="philhealthNumber"></dd></div>
                            <div class="col"><dt>Pag-IBIG (HDMF) Number</dt><dd class="mb-0 tabular-nums" data-portal-field="pagibigNumber"></dd></div>
                            <div class="col"><dt>Tax Identification Number (TIN)</dt><dd class="mb-0 tabular-nums" data-portal-field="tin"></dd></div>
                            <div class="col"><dt>BIR Number</dt><dd class="mb-0 tabular-nums" data-portal-field="birNumber"></dd></div>
                        </dl>
                    </section>
                </div>
            </div>

            {{-- Edit mode: the editable fields, with the read-only ones beside them for context. --}}
            <form class="card employee-form-card d-none" novalidate data-portal-form>
                <p class="text-body-secondary mb-4">
                    You can update your civil status, phone number, and address. Your other details are managed by HR.
                </p>

                <section class="mb-4">
                    <h2 class="employee-form-section">Personal Information</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="portal-name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="portal-name" readonly data-portal-input="name">
                        </div>

                        <div class="col-md-6">
                            <label for="portal-birthdate" class="form-label">Birthdate</label>
                            <input type="text" class="form-control" id="portal-birthdate" readonly data-portal-input="birthdate">
                        </div>

                        <div class="col-md-6">
                            <label for="portal-sex" class="form-label">Sex</label>
                            <input type="text" class="form-control" id="portal-sex" readonly data-portal-input="sex">
                        </div>

                        <div class="col-md-6">
                            <label for="portal-civil-status" class="form-label">Civil Status</label>
                            <select class="form-select" id="portal-civil-status" name="civilStatus">
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
                            <label for="portal-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="portal-email" readonly data-portal-input="email">
                        </div>

                        <div class="col-md-6">
                            <label for="portal-phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="portal-phone" name="phone" placeholder="+63 9XX XXX XXXX"
                                autocomplete="off" aria-describedby="portal-phone-error">
                            <div class="invalid-feedback" id="portal-phone-error"></div>
                        </div>

                        <div class="col-12">
                            <label for="portal-address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="portal-address" name="address" autocomplete="off">
                        </div>
                    </div>
                </section>

                <div class="d-flex justify-content-end flex-wrap gap-2 mt-2">
                    <button type="button" class="btn btn-outline-secondary" data-portal-edit-cancel>Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>

            <x-password-change-section account="employee" />
        </div>
    </div>
@endsection
