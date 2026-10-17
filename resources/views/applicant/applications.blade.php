@extends('layouts.applicant')

@section('title', 'Applications · '.config('app.name'))

{{--
    The applicant's applications, filled in by resources/js/applicant-applications.js from the same Recruitment data the
    HR pages use, so each status and schedule follows what HR does. An applicant's applications are the ones from
    their email address; the application they came in with is found by the ?ref= in the address (there are no
    accounts). Applicants can open an application's details and apply for another open position, but nothing here
    changes a recruitment status.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="applicant-applications"
        data-details-url="{{ route('applicant.application') }}" data-apply-url="{{ route('applicant.apply') }}">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Applications</h1>
                <p class="text-body-secondary mt-1 mb-0" data-applications-subtitle>The positions you have applied for.</p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-secondary" href="{{ route('applicant.positions') }}" data-applicant-link>Browse Open Positions</a>
                <a class="btn btn-primary d-inline-flex align-items-center gap-2" href="{{ route('applicant.apply') }}"
                    data-applications-apply>
                    <x-icon name="plus" />
                    Apply for a Position
                </a>
            </div>
        </div>

        <div class="card d-none" data-applicant-missing>
            <div class="employees-empty">
                <strong>No applications found</strong>
                Open this page from your application confirmation, or apply for a position.
                <div class="d-flex justify-content-center flex-wrap gap-2 mt-3">
                    <a class="btn btn-primary" href="{{ route('applicant.apply') }}">Apply for a Position</a>
                    <a class="btn btn-outline-secondary" href="{{ route('login') }}">Back to Sign In</a>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden d-none" data-applicant-content>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 employees-table">
                    <thead>
                        <tr>
                            <th scope="col">Position</th>
                            <th scope="col">Submitted</th>
                            <th scope="col">Status</th>
                            <th scope="col">Next Step</th>
                        </tr>
                    </thead>
                    <tbody data-applications-rows></tbody>
                </table>
            </div>

            <div class="employees-empty d-none" data-applications-empty>
                <strong>No applications yet</strong>
                Apply for a position and it will appear here.
            </div>

            <div class="employees-footer d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <span aria-live="polite" data-applications-summary></span>
            </div>
        </div>
    </div>
@endsection
