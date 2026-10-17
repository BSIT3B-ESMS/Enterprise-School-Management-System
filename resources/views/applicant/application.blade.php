@extends('layouts.applicant')

@section('title', 'Application Details · '.config('app.name'))

{{--
    The details and progress of one application (the one in the ?ref= in the address, opened from the Applications
    list), filled in by resources/js/applicant-portal.js from the same Recruitment data the HR pages use, so its
    status and schedule follow what HR does. There are no accounts, and nothing here changes the application: the
    applicant can only follow it.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="applicant-application">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Application Details</h1>
                <p class="text-body-secondary mt-1 mb-0">Follow where your application stands.</p>
            </div>

            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('applicant.applications') }}"
                data-applicant-link>
                <x-icon name="back" />
                Back to Applications
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
                    <section class="card dashboard-panel" aria-labelledby="applicant-details-title">
                        <h2 id="applicant-details-title">Your application</h2>
                        <p class="dashboard-panel-subtitle">What you submitted</p>

                        <dl class="row row-cols-1 row-cols-sm-2 g-3 mb-0 employee-details">
                            <div class="col"><dt>Position</dt><dd class="mb-0" data-applicant-field="jobTitle"></dd></div>
                            <div class="col"><dt>Department</dt><dd class="mb-0" data-applicant-field="department"></dd></div>
                            <div class="col"><dt>Application Date</dt><dd class="mb-0" data-applicant-field="appliedOn"></dd></div>
                            <div class="col"><dt>Phone</dt><dd class="mb-0" data-applicant-field="phone"></dd></div>
                            <div class="col-12"><dt>Email</dt><dd class="mb-0 text-break" data-applicant-field="email"></dd></div>
                        </dl>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
