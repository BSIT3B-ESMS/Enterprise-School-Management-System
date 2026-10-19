@extends('layouts.applicant')

@section('title', 'Applications · '.config('app.name'))

{{--
    The signed-in applicant's applications, filled in by resources/js/applicant-applications.js from the same Recruitment
    data the HR pages use, so each status and schedule follows what HR does. Each row opens that application's details.
    Nothing here changes a recruitment status.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="applicant-applications" data-details-url="{{ route('applicant.applications') }}">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Applications</h1>
                <p class="text-body-secondary mt-1 mb-0">The positions you have applied for.</p>
            </div>

            <a class="btn btn-primary d-inline-flex align-items-center gap-2" href="{{ route('applicant.positions') }}">
                <x-icon name="plus" />
                Apply for a Position
            </a>
        </div>

        <div class="card overflow-hidden">
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
                Browse the open positions and apply. Your applications will appear here.
            </div>

            <div class="employees-footer d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <span aria-live="polite" data-applications-summary></span>
            </div>
        </div>
    </div>
@endsection
