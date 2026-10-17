@extends('layouts.applicant')

@section('title', 'Dashboard · '.config('app.name'))

@php
    $quickLinks = [
        ['icon' => 'user', 'label' => 'Profile', 'about' => 'Your details and contact information', 'route' => 'applicant.profile'],
        ['icon' => 'calendar', 'label' => 'Calendar', 'about' => 'Your screening and interview dates', 'route' => 'applicant.calendar'],
        ['icon' => 'job-titles', 'label' => 'Open Positions', 'about' => 'See what else is open', 'route' => 'applicant.positions'],
        ['icon' => 'recruitment', 'label' => 'Applications', 'about' => 'Follow your applications', 'route' => 'applicant.applications'],
    ];
@endphp

{{--
    The applicant's dashboard, filled in by resources/js/applicant-dashboard.js from the same Recruitment data the HR
    pages use, so the status and schedule follow what HR does. The application is found by the ?ref= in the address
    (there are no accounts). Nothing here changes the application.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="applicant-dashboard">
        <div>
            <h1 class="page-title" data-applicant-greeting>Welcome</h1>
            <p class="text-body-secondary mt-1 mb-0">Here is where your application stands.</p>
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
            <div class="row row-cols-1 row-cols-sm-3 g-3" data-applicant-kpis></div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <section class="card dashboard-panel" aria-labelledby="applicant-status-title">
                        <h2 id="applicant-status-title">Recruitment status</h2>
                        <p class="dashboard-panel-subtitle">Where your application is in the process</p>

                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                            <span class="status-pill" data-applicant-status-pill></span>
                            <span class="text-body-secondary" data-applicant-field="role"></span>
                        </div>
                        <p role="status" data-applicant-status-message></p>

                        <ol class="recruitment-steps mb-0" data-applicant-steps></ol>
                    </section>
                </div>

                <div class="col-lg-5">
                    <section class="card dashboard-panel" aria-labelledby="applicant-summary-title">
                        <h2 id="applicant-summary-title">Application summary</h2>
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

            <div class="row g-4">
                <div class="col-lg-7">
                    <section class="card dashboard-panel" aria-labelledby="applicant-upcoming-title">
                        <h2 id="applicant-upcoming-title">Upcoming screening and interview</h2>
                        <p class="dashboard-panel-subtitle">Set by HR</p>

                        <ul class="list-group list-group-flush dashboard-list" data-applicant-upcoming></ul>
                        <p class="text-body-secondary mb-0 d-none" data-applicant-upcoming-empty>
                            No screening or interview is scheduled yet.
                        </p>
                    </section>
                </div>

                <div class="col-lg-5">
                    <section class="card dashboard-panel" aria-labelledby="applicant-links-title">
                        <h2 id="applicant-links-title">Quick links</h2>
                        <p class="dashboard-panel-subtitle">Jump to a section</p>

                        <div class="row row-cols-1 row-cols-sm-2 g-3">
                            @foreach ($quickLinks as $link)
                                <div class="col">
                                    <a class="quick-link" href="{{ route($link['route']) }}" data-applicant-link>
                                        <x-icon :name="$link['icon']" />
                                        <span class="quick-link-label">{{ $link['label'] }}</span>
                                        <small>{{ $link['about'] }}</small>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
