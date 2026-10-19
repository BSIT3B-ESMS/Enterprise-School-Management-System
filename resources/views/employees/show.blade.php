@extends('layouts.admin')

@section('title', 'Employee Profile · '.config('app.name'))

@php
    $tabs = [
        'personal' => 'Personal Information',
        'employment' => 'Employment Information',
        'contact' => 'Contact and Emergency',
        'government' => 'Government and Tax',
        'compensation' => 'Compensation and Benefits',
        'history' => 'Education and History',
        'performance' => 'Performance and Training',
        'leave' => 'Leave and Attendance',
        'separation' => 'Disciplinary and Separation',
    ];
@endphp

{{--
    Employee profile (201 file). Every value is filled in by resources/js/employee-profile.js from
    resources/js/mock-data.js; sections the system does not record yet show an empty state.
    Edit swaps the tabs for the shared employee form on this page (resources/js/employee-form.js).
    The Employees list passes its search, filters, sort, and page in the query string, and
    employee-profile.js adds it to Back to Employees.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="employee-profile" data-employee-id="{{ $employee }}">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <h1 class="page-title">Employee Profile</h1>

            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('employees.index') }}"
                data-employee-back-link data-employees-url="{{ route('employees.index') }}">
                <x-icon name="back" />
                Back to Employees
            </a>
        </div>

        <div class="card employees-filters d-none" data-profile-missing>
            <div class="employees-empty">
                <strong>Employee not found</strong>
                There is no employee with the ID {{ $employee }}. They may have been deleted.
                <div class="mt-3">
                    <a class="btn btn-outline-primary" href="{{ route('employees.index') }}">Back to Employees</a>
                </div>
            </div>
        </div>

        <div class="d-grid gap-4 d-none" data-profile>
            <section class="card employee-form-card">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="profile-avatar" aria-hidden="true" data-profile-field="initials"></span>
                    <div class="min-w-0 flex-grow-1">
                        <h2 class="fs-4 mb-1" data-profile-field="name"></h2>
                        <div class="d-flex align-items-center gap-2 flex-wrap text-body-secondary">
                            <span class="ems-chip tabular-nums" data-profile-field="id"></span>
                            <span data-profile-field="role"></span>
                            <span class="status-pill" data-profile-status-pill></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2" data-profile-actions>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
                            data-bs-target="#employee-archive-modal" data-employee-archive="{{ $employee }}">Archive</button>
                        <button type="button" class="btn btn-primary" data-profile-edit-start>Edit</button>
                    </div>
                </div>
            </section>

            <div class="card employee-form-card" data-profile-view>
                <ul class="nav nav-underline profile-tabs mb-4" role="tablist">
                    @foreach ($tabs as $key => $label)
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link{{ $loop->first ? ' active' : '' }}" id="profile-{{ $key }}-tab"
                                data-bs-toggle="tab" data-bs-target="#profile-{{ $key }}" role="tab"
                                aria-controls="profile-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content">
                    <section class="tab-pane fade show active" id="profile-personal" role="tabpanel"
                        aria-labelledby="profile-personal-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                            <div class="col"><dt>Full Name</dt><dd class="mb-0" data-profile-field="name"></dd></div>
                            <div class="col"><dt>Employee ID</dt><dd class="mb-0 tabular-nums" data-profile-field="id"></dd></div>
                            <div class="col"><dt>Birthdate</dt><dd class="mb-0" data-profile-field="birthdate"></dd></div>
                            <div class="col"><dt>Sex</dt><dd class="mb-0" data-profile-field="sex"></dd></div>
                            <div class="col"><dt>Civil Status</dt><dd class="mb-0" data-profile-field="civilStatus"></dd></div>
                        </dl>
                    </section>

                    <section class="tab-pane fade" id="profile-employment" role="tabpanel"
                        aria-labelledby="profile-employment-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                            <div class="col"><dt>Department</dt><dd class="mb-0" data-profile-field="department"></dd></div>
                            <div class="col"><dt>Job Title</dt><dd class="mb-0" data-profile-field="jobTitle"></dd></div>
                            <div class="col"><dt>Base Salary</dt><dd class="mb-0 tabular-nums" data-profile-field="salary"></dd></div>
                            <div class="col"><dt>Date Hired</dt><dd class="mb-0" data-profile-field="hiredOn"></dd></div>
                            <div class="col"><dt>Employment Status</dt><dd class="mb-0" data-profile-field="status"></dd></div>
                        </dl>

                        <x-profile-documents class="mt-4" document="hiringRequirements" label="Hiring Requirements" />
                    </section>

                    <section class="tab-pane fade" id="profile-contact" role="tabpanel"
                        aria-labelledby="profile-contact-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-4 employee-details">
                            <div class="col"><dt>Email</dt><dd class="mb-0" data-profile-field="email"></dd></div>
                            <div class="col"><dt>Phone</dt><dd class="mb-0" data-profile-field="phone"></dd></div>
                            <div class="col-12"><dt>Address</dt><dd class="mb-0" data-profile-field="address"></dd></div>
                        </dl>

                        <h3 class="employee-form-section">Emergency Contact</h3>
                        <x-profile-empty-state title="No emergency contact on file" />
                    </section>

                    <section class="tab-pane fade" id="profile-government" role="tabpanel"
                        aria-labelledby="profile-government-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-4 employee-details">
                            <div class="col"><dt>SSS Number</dt><dd class="mb-0 tabular-nums" data-profile-field="sssNumber"></dd></div>
                            <div class="col"><dt>PhilHealth Number</dt><dd class="mb-0 tabular-nums" data-profile-field="philhealthNumber"></dd></div>
                            <div class="col"><dt>Pag-IBIG (HDMF) Number</dt><dd class="mb-0 tabular-nums" data-profile-field="pagibigNumber"></dd></div>
                            <div class="col"><dt>Tax Identification Number (TIN)</dt><dd class="mb-0 tabular-nums" data-profile-field="tin"></dd></div>
                            <div class="col"><dt>BIR Number</dt><dd class="mb-0 tabular-nums" data-profile-field="birNumber"></dd></div>
                        </dl>

                        <x-profile-documents class="mb-4" document="birForm2316" label="BIR Form 2316" />

                        <h3 class="employee-form-section">Contribution Enrollment <small>From the employee’s benefits</small></h3>
                        <ul class="list-group list-group-flush dashboard-list" data-profile-government></ul>
                    </section>

                    <section class="tab-pane fade" id="profile-compensation" role="tabpanel"
                        aria-labelledby="profile-compensation-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-4 employee-details">
                            <div class="col"><dt>Base Salary (monthly)</dt><dd class="mb-0 tabular-nums" data-profile-field="salary"></dd></div>
                            <div class="col"><dt>Job Title</dt><dd class="mb-0" data-profile-field="jobTitle"></dd></div>
                        </dl>

                        <h3 class="employee-form-section">Benefits</h3>
                        <div class="d-flex flex-wrap gap-2" data-profile-benefits></div>
                    </section>

                    <section class="tab-pane fade" id="profile-history" role="tabpanel"
                        aria-labelledby="profile-history-tab" tabindex="0">
                        <x-profile-documents class="mb-4" document="education" label="Education Transcripts or Diplomas" />
                        <x-profile-documents class="mb-4" document="workHistory" label="Work History" />

                        <h3 class="employee-form-section">
                            Employment History <small>Current position and recent changes to this record</small>
                        </h3>
                        <ul class="list-group list-group-flush dashboard-list" data-profile-history></ul>
                    </section>

                    <section class="tab-pane fade" id="profile-performance" role="tabpanel"
                        aria-labelledby="profile-performance-tab" tabindex="0">
                        <x-profile-documents document="performance" label="Performance Assessments" />
                    </section>

                    <section class="tab-pane fade" id="profile-leave" role="tabpanel"
                        aria-labelledby="profile-leave-tab" tabindex="0">
                        <dl class="row row-cols-1 row-cols-md-2 g-3 mb-4 employee-details">
                            <div class="col"><dt>Current Status</dt><dd class="mb-0" data-profile-field="status"></dd></div>
                        </dl>

                        <h3 class="employee-form-section">Leave Requests <small>Also shown on the Calendar</small></h3>
                        <ul class="list-group list-group-flush dashboard-list" data-profile-leave></ul>
                        <div class="d-none" data-profile-leave-empty>
                            <x-profile-empty-state title="No leave requests on file">
                                This employee has not filed any leave.
                            </x-profile-empty-state>
                        </div>
                    </section>

                    <section class="tab-pane fade" id="profile-separation" role="tabpanel"
                        aria-labelledby="profile-separation-tab" tabindex="0">
                        <x-profile-documents class="mb-4" document="correctiveActions" label="Corrective Actions" />
                        <x-profile-documents class="mb-4" document="clearances" label="Clearances" />
                        <x-profile-documents class="mb-4" document="postEmployment" label="Post-Employment Information" />

                        <h3 class="employee-form-section">Separation</h3>
                        <x-profile-empty-state title="No separation record">
                            <span data-profile-field="separationNote"></span>
                        </x-profile-empty-state>
                    </section>
                </div>
            </div>

            {{-- Edit mode: the same fields as the New Employee page, prefilled by employee-form.js. --}}
            <form class="card employee-form-card d-none" novalidate data-employee-form data-profile-edit-form>
                <x-employee-form-fields heading-tag="h2" />

                <div class="d-flex justify-content-end flex-wrap gap-2 mt-2">
                    <button type="button" class="btn btn-outline-secondary" data-profile-edit-cancel>Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <x-employee-archive-modal />
@endsection
