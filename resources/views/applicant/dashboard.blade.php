@extends('layouts.applicant')

@section('title', 'Dashboard · '.config('app.name'))

@php
    $quickLinks = [
        ['icon' => 'user', 'label' => 'Profile', 'about' => 'Your details and contact information', 'route' => 'applicant.profile'],
        ['icon' => 'calendar', 'label' => 'Calendar', 'about' => 'Your screening and interview dates', 'route' => 'applicant.calendar'],
        ['icon' => 'job-titles', 'label' => 'Open Positions', 'about' => 'Find a position to apply for', 'route' => 'applicant.positions'],
        ['icon' => 'recruitment', 'label' => 'Applications', 'about' => 'Follow your applications', 'route' => 'applicant.applications'],
    ];
@endphp

{{--
    The signed-in applicant's dashboard, filled in by resources/js/applicant-dashboard.js from the same Recruitment data
    the HR pages use, so statuses and schedules follow what HR does. Nothing here changes an application.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="applicant-dashboard" data-application-url="{{ route('applicant.applications') }}"
        data-apply-url="{{ route('applicant.apply') }}">
        <div>
            <h1 class="page-title" data-applicant-greeting>Welcome</h1>
            <p class="text-body-secondary mt-1 mb-0">Here is where your applications stand.</p>
        </div>

        {{-- The position chosen before registering or logging in (?position=), kept so the application can be continued. --}}
        <div class="alert alert-info d-none justify-content-between align-items-center gap-3 flex-wrap mb-0" role="status"
            data-applicant-pending-position>
            <span>You chose <strong data-applicant-pending-name></strong>. Continue with your application.</span>
            <a class="btn btn-sm btn-primary" href="{{ route('applicant.apply') }}" data-applicant-pending-apply>Continue Application</a>
        </div>

        <div class="row row-cols-1 row-cols-sm-3 g-3" data-applicant-kpis></div>

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="card dashboard-panel" aria-labelledby="applicant-applications-title">
                    <h2 id="applicant-applications-title">Your applications</h2>
                    <p class="dashboard-panel-subtitle">Current status of each application</p>

                    <ul class="list-group list-group-flush dashboard-list" data-applicant-application-list></ul>

                    <div class="d-none" data-applicant-no-applications>
                        <p class="text-body-secondary">You have not applied for a position yet.</p>
                        <a class="btn btn-primary" href="{{ route('applicant.positions') }}">Browse Open Positions</a>
                    </div>
                </section>
            </div>

            <div class="col-lg-5">
                <section class="card dashboard-panel" aria-labelledby="applicant-upcoming-title">
                    <h2 id="applicant-upcoming-title">Upcoming screenings and interviews</h2>
                    <p class="dashboard-panel-subtitle">Set by HR</p>

                    <ul class="list-group list-group-flush dashboard-list" data-applicant-upcoming></ul>
                    <p class="text-body-secondary mb-0 d-none" data-applicant-upcoming-empty>
                        No screening or interview is scheduled yet.
                    </p>
                </section>
            </div>
        </div>

        <section class="card dashboard-panel" aria-labelledby="applicant-links-title">
            <h2 id="applicant-links-title">Quick links</h2>
            <p class="dashboard-panel-subtitle">Jump to a section</p>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
                @foreach ($quickLinks as $link)
                    <div class="col">
                        <a class="quick-link" href="{{ route($link['route']) }}">
                            <x-icon :name="$link['icon']" />
                            <span class="quick-link-label">{{ $link['label'] }}</span>
                            <small>{{ $link['about'] }}</small>
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
