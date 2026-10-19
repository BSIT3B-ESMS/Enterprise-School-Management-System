@extends('layouts.applicant')

@section('title', 'Application Details · '.config('app.name'))

@php
    // The documents the applicant submitted with the application (file names only).
    $documents = [
        'hiringRequirements' => 'Hiring Requirements',
        'education' => 'Education Transcripts or Diplomas',
        'workHistory' => 'Work History',
    ];
@endphp

{{--
    One of the signed-in applicant's applications, filled in by resources/js/applicant-application.js from the same
    Recruitment data the HR pages use, so its status and schedule follow what HR does. An application that belongs to
    someone else shows as not found. Nothing here changes the application: the applicant can only follow it.
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

            <section class="card dashboard-panel" aria-labelledby="applicant-progress-title">
                <h2 id="applicant-progress-title">Progress</h2>
                <p class="dashboard-panel-subtitle">The stages an application goes through</p>

                <ol class="recruitment-steps mb-0" data-applicant-steps></ol>
            </section>

            <div class="row g-4">
                <div class="col-lg-7">
                    <section class="card dashboard-panel" aria-labelledby="applicant-schedule-title">
                        <h2 id="applicant-schedule-title">Screening and interview</h2>
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
                            <div class="col"><dt>Department</dt><dd class="mb-0" data-applicant-field="department"></dd></div>
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
                    <div class="col"><dt>Full Name</dt><dd class="mb-0" data-applicant-field="name"></dd></div>
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
