@extends('layouts.employee')

@section('title', 'Calendar · '.config('app.name'))

@php
    // Leave goes through Filed (submitted), Pending (under review), and Approved, the same as in the HR Calendar.
    $eventStatuses = ['Filed', 'Pending', 'Approved'];
    $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
@endphp

{{--
    The signed-in employee's own leave on the same calendar the HR pages use (resources/js/calendar.js, in its
    "own" mode), drawn from the mock data. Only this employee's leave is passed to the page, and nothing here
    changes a request's status; HR approves leave.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="calendar" data-calendar-scope="own">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Calendar</h1>
                <p class="text-body-secondary mt-1 mb-0">Your filed, pending, and approved leave.</p>
            </div>

            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal"
                data-bs-target="#leave-request-modal">
                <x-icon name="plus" />
                Request Leave
            </button>
        </div>

        <div class="card employees-filters">
            <div class="row g-3 align-items-start">
                <fieldset class="col-lg-9">
                    <legend class="form-label small fw-semibold mb-2">Leave Status</legend>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($eventStatuses as $status)
                            <input type="checkbox" class="btn-check" id="calendar-status-{{ strtolower($status) }}" value="{{ $status }}"
                                autocomplete="off" checked data-calendar-status-filter>
                            <label class="btn btn-sm btn-outline-primary" for="calendar-status-{{ strtolower($status) }}">{{ $status }}</label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="col-lg-3 d-flex justify-content-lg-end align-self-lg-end">
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
                    <h2 class="employee-form-section" id="calendar-details-title">Leave Details</h2>
                    <div data-calendar-details></div>
                    <p class="text-body-secondary mb-0" data-calendar-details-empty>Select a leave to view its details.</p>
                </section>
            </div>
        </div>
    </div>

    {{-- Files leave for HR to approve (resources/js/portal-leave-request.js); it shows as Filed until HR decides. --}}
    <div class="modal fade" id="leave-request-modal" tabindex="-1" aria-labelledby="leave-request-modal-title"
        aria-hidden="true" data-leave-request-modal>
        <div class="modal-dialog">
            <form class="modal-content" novalidate data-leave-request-form>
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="leave-request-modal-title">Request Leave</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="leave-type" class="form-label">Leave Type</label>
                            <select class="form-select" id="leave-type" name="type" aria-describedby="leave-type-error" required
                                data-leave-type-options></select>
                            <div class="invalid-feedback" id="leave-type-error"></div>
                        </div>

                        <div class="col-sm-6">
                            <label for="leave-start" class="form-label">First Day</label>
                            <input type="date" class="form-control" id="leave-start" name="startDate"
                                aria-describedby="leave-start-error" required>
                            <div class="invalid-feedback" id="leave-start-error"></div>
                        </div>

                        <div class="col-sm-6">
                            <label for="leave-end" class="form-label">Last Day</label>
                            <input type="date" class="form-control" id="leave-end" name="endDate"
                                aria-describedby="leave-end-error" required>
                            <div class="invalid-feedback" id="leave-end-error"></div>
                        </div>
                    </div>

                    <p class="small text-body-secondary mt-3 mb-0">HR approves leave. Until then it shows as Filed.</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">File Request</button>
                </div>
            </form>
        </div>
    </div>
@endsection
