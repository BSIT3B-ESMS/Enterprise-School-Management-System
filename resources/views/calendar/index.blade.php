@extends('layouts.admin')

@section('title', (request()->query('view') === 'leave-requests' ? 'Leave Requests' : 'Calendar').' · '.config('app.name'))

@php
    $eventTypes = [
        'leave' => 'Employee Leave',
        'demo-interview' => 'Applicant Demo Interview',
        'interview' => 'Applicant Final Interview',
        'other' => 'Other Applicant Event',
    ];
    // Leave shows while Pending (dashed) or once Approved (solid); rejected and cancelled requests are listed only in
    // Leave Requests below.
    $eventStatuses = ['Pending', 'Approved', 'Scheduled', 'Completed'];
    $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
@endphp

{{--
    Calendar of employee leave and applicant demo interviews and final interviews. Everything is rendered by
    resources/js/calendar.js from the same mock data as Employees and Recruitment
    (resources/js/calendar-events.js builds the events), so changes there appear here.
    Filters: event types and statuses are multi-select; an event shows when its type AND its status
    are selected.
    Clicking a date opens its events and notes; clicking an event opens its details (venue, notes, rescheduling history,
    with Reschedule and Cancel Schedule). Unscheduled Applicants (top right) lists applicants waiting for their next
    interview with nothing scheduled; choosing one, or Schedule on This Date, opens the scheduling form. Every change is
    confirmed first. Add Event (beside the month navigation) and clicking a date with no events open the event form
    (the date prefilled); events HR adds are part of the shared mock data and open in the same details modal. Handled by
    resources/js/calendar-scheduling.js.
--}}
@section('content')
    {{--
        Leave Requests is a separate page from the Calendar: the same route with ?view=leave-requests (no extra route),
        reached from its own sidebar item. Neither page repeats the other's content.
    --}}
    @if (request()->query('view') === 'leave-requests')
        <div class="d-grid gap-4">
            <x-leave-requests-section scope="hr" />
        </div>

        <x-leave-request-modals scope="hr" />
    @else
    <div class="d-grid gap-4" data-page="calendar" data-calendar-interactive data-employees-url="{{ url('/employees') }}"
        data-leave-requests-url="{{ route('calendar.index', ['view' => 'leave-requests']) }}"
        data-recruitment-url="{{ route('recruitment.index') }}">
        <div>
            <h1 class="page-title">Calendar</h1>
            <p class="text-body-secondary mt-1 mb-0">Employee leave and applicant demo interviews and final interviews.</p>
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
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" data-calendar-add-event>
                                <x-icon name="plus" />
                                Add Event
                            </button>
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

            <div class="col-xl-4">
                <section class="card employee-form-card" aria-labelledby="calendar-unscheduled-title" data-unscheduled-panel>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <h2 class="fs-5 mb-0" id="calendar-unscheduled-title">Unscheduled Applicants</h2>
                        <span class="ems-chip tabular-nums" data-unscheduled-count></span>
                    </div>
                    <p class="small text-body-secondary">
                        Applicants waiting for their next interview with nothing scheduled. Choose one to schedule them.
                    </p>
                    <div class="list-group list-group-flush calendar-day-list" data-unscheduled-list></div>
                    <p class="text-body-secondary mb-0 d-none" data-unscheduled-empty>
                        Every applicant waiting for an interview has a schedule.
                    </p>
                </section>
            </div>
        </div>
    </div>

    <x-calendar-schedule-modals />
    @endif
@endsection
