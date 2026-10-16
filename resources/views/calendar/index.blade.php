@extends('layouts.admin')

@section('title', 'Calendar · '.config('app.name'))

@php
    $eventTypes = [
        'leave' => 'Employee Leave',
        'screening' => 'Applicant Screening',
        'interview' => 'Applicant Interview',
    ];
    $eventStatuses = ['Filed', 'Pending', 'Approved', 'Scheduled', 'Completed'];
    $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
@endphp

{{--
    Calendar of employee leave and applicant screenings and interviews. Everything is rendered by
    resources/js/calendar.js from the same mock data as Employees and Recruitment
    (resources/js/calendar-events.js builds the events), so changes there appear here.
    Filters: event types and statuses are multi-select; an event shows when its type AND its status
    are selected.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="calendar" data-employees-url="{{ url('/employees') }}"
        data-recruitment-url="{{ route('recruitment.index') }}">
        <div>
            <h1 class="page-title">Calendar</h1>
            <p class="text-body-secondary mt-1 mb-0">Employee leave and applicant screenings and interviews.</p>
        </div>

        <div class="card employees-filters">
            <div class="row g-3 align-items-start">
                <fieldset class="col-lg-5">
                    <legend class="form-label small fw-semibold mb-2">Event Type</legend>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($eventTypes as $value => $label)
                            <input type="checkbox" class="btn-check" id="calendar-type-{{ $value }}" value="{{ $value }}"
                                autocomplete="off" checked data-calendar-type-filter>
                            <label class="btn btn-sm btn-outline-primary"
                                for="calendar-type-{{ $value }}">{{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="col-lg-5">
                    <legend class="form-label small fw-semibold mb-2">Event Status</legend>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($eventStatuses as $status)
                            <input type="checkbox" class="btn-check" id="calendar-status-{{ strtolower($status) }}" value="{{ $status }}"
                                autocomplete="off" checked data-calendar-status-filter>
                            <label class="btn btn-sm btn-outline-primary" for="calendar-status-{{ strtolower($status) }}">{{ $status }}</label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="col-lg-2 d-flex justify-content-lg-end align-self-lg-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-calendar-clear>Clear Filters</button>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-3 align-items-center mt-3 pt-3 border-top small" aria-label="Legend" role="list"
                data-calendar-legend></div>
            <p class="small text-body-secondary mb-0 mt-2 d-none" data-calendar-data-notice></p>
        </div>

        <div class="alert alert-danger mb-0 d-none" role="alert" data-calendar-error></div>

        <div class="row g-4 align-items-start">
            <div class="col-xl-8">
                <section class="card employee-form-card" aria-labelledby="calendar-month-title">
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                        <h2 class="fs-5 mb-0" id="calendar-month-title" aria-live="polite" data-calendar-month-title></h2>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-calendar-nav="-1" aria-label="Previous month">
                                <x-icon name="chevron-left" />
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-calendar-today>Today</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-calendar-nav="1" aria-label="Next month">
                                <x-icon name="chevron-right" />
                            </button>
                        </div>
                    </div>

                    <p class="alert alert-secondary py-2 small d-none" role="status" data-calendar-month-empty></p>

                    <div class="calendar-weekdays" aria-hidden="true">
                        @foreach ($weekdays as $weekday)
                            <span>{{ $weekday }}</span>
                        @endforeach
                    </div>
                    <div class="calendar-grid" data-calendar-grid></div>
                </section>
            </div>

            <div class="col-xl-4 d-grid gap-4">
                <section class="card employee-form-card" aria-labelledby="calendar-day-title">
                    <h2 class="employee-form-section" id="calendar-day-title" data-calendar-day-title></h2>
                    <div class="list-group list-group-flush calendar-day-list" data-calendar-day-events></div>
                    <p class="text-body-secondary mb-0 d-none" data-calendar-day-empty></p>
                </section>

                <section class="card employee-form-card" aria-labelledby="calendar-details-title">
                    <h2 class="employee-form-section" id="calendar-details-title">Event Details</h2>
                    <div class="alert alert-danger py-2 d-none" role="alert" data-calendar-details-error></div>
                    <div data-calendar-details></div>
                    <p class="text-body-secondary mb-0" data-calendar-details-empty>Select an event to view its details.</p>
                </section>
            </div>
        </div>
    </div>
@endsection
