@extends('layouts.applicant')

@section('title', 'Dashboard · '.config('app.name'))

@php
    use App\Enums\ApplicationStatus;
@endphp

@section('content')
    <div class="d-grid gap-4">
        <div>
            <h1 class="page-title">Welcome, {{ $profile->first_name }}</h1>
            <p class="text-body-secondary mt-1 mb-0">Follow your applications and your interview schedule here.</p>
        </div>

        @unless ($profile->isComplete())
            <div class="alert alert-warning mb-0" role="status">
                Your profile is not complete yet. <a href="{{ route('applicant.profile') }}">Complete it once</a> to be able to apply.
            </div>
        @endunless

        @if ($hired)
            <div class="alert alert-success mb-0" role="status">
                You are hired as <strong>{{ $hired->jobTitle->title }}</strong>.
                <a href="{{ route('applicant.applications.show', $hired) }}">See how to sign in as an employee.</a>
            </div>
        @endif

        @foreach ($offers as $application)
            <div class="alert alert-primary mb-0" role="status">
                You have a job offer for <strong>{{ $application->jobTitle->title }}</strong>.
                <a href="{{ route('applicant.applications.show', $application) }}">Read it and answer.</a>
            </div>
        @endforeach

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="card dashboard-panel">
                    <h2>My applications</h2>
                    <p class="dashboard-panel-subtitle">{{ $applications->count() }} in all</p>
                    <div class="row row-cols-2 row-cols-sm-3 g-2 mb-3">
                        @foreach (\App\Enums\ApplicationStatus::pipeline() as $status)
                            <div class="col">
                                <div class="quick-link">
                                    <span class="quick-link-label tabular-nums">{{ $counts[$status->value] }}</span>
                                    <small>{{ $status->label() }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <ul class="list-group list-group-flush dashboard-list">
                        @forelse ($applications->take(5) as $application)
                            <li class="list-group-item px-0 d-flex justify-content-between gap-3">
                                <a class="text-reset" href="{{ route('applicant.applications.show', $application) }}">{{ $application->jobTitle->title }}</a>
                                <span class="status-pill {{ $application->status->cssClass() }}">{{ $application->status->label() }}</span>
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-body-secondary">You have not applied yet. <a href="{{ route('applicant.positions') }}">Browse the open positions</a>.</li>
                        @endforelse
                    </ul>
                </section>
            </div>

            <div class="col-lg-5">
                <section class="card dashboard-panel">
                    <h2>Next demo interview</h2>
                    @if ($nextInterview)
                        <p class="dashboard-panel-subtitle">{{ $nextInterview->application->jobTitle->title }}</p>
                        <p class="mb-1 fw-medium">{{ $nextInterview->starts_at->format('l, M j, Y') }}</p>
                        <p class="mb-1">{{ $nextInterview->starts_at->format('g:i A') }} to {{ $nextInterview->ends_at->format('g:i A') }}</p>
                        <p class="mb-2 text-body-secondary">{{ $nextInterview->placeLabel() }}</p>
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('applicant.applications.show', $nextInterview->application) }}">Details</a>
                    @else
                        <p class="dashboard-panel-subtitle mb-0">Nothing is scheduled. HR will schedule your demo interview after reviewing your documents.</p>
                    @endif
                </section>
            </div>
        </div>
    </div>
@endsection
