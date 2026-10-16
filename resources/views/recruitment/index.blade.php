@extends('layouts.admin')

@section('title', 'Recruitment · '.config('app.name'))

@php
    $columns = [
        'name' => 'Applicant',
        'jobTitle' => 'Position Applied For',
        'appliedOn' => 'Application Date',
        'status' => 'Status',
    ];
@endphp

{{--
    The pipeline, rows, filter options, sorting, and pagination are rendered by resources/js/recruitment.js
    from resources/js/mock-data.js. Clicking a row (or pressing Enter/Space on it) opens the application
    details modal, which shows the recruitment workflow and offers Edit and Delete. The status follows the
    workflow (scheduled and completed screenings and interviews, offer, hiring), so it is only displayed here.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="recruitment">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Recruitment</h1>
                <p class="text-body-secondary mt-1 mb-0">Track applicants from first application to hire.</p>
            </div>

            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal"
                data-bs-target="#application-form-modal" data-application-add>
                <x-icon name="plus" />
                Add Application
            </button>
        </div>

        <section class="card dashboard-panel" aria-labelledby="recruitment-pipeline-title">
            <h2 id="recruitment-pipeline-title">Recruitment pipeline</h2>
            <p class="dashboard-panel-subtitle" data-application-total></p>

            <div class="progress-stacked dashboard-pipeline" data-application-pipeline></div>

            {{-- Each stage button filters the table to that status; pressing it again clears the filter. --}}
            <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-6 g-2" role="group" aria-label="Filter by status"
                data-application-stages></div>
        </section>

        <div class="card employees-filters">
            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <label for="application-search" class="visually-hidden">Search applications</label>
                    <div class="search-field">
                        <x-icon name="search" />
                        <input type="search" class="form-control" id="application-search" placeholder="Search applicants..."
                            autocomplete="off" data-application-search>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="application-status-filter" class="visually-hidden">Status</label>
                    <select class="form-select" id="application-status-filter" data-application-filter="status"></select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="application-department-filter" class="visually-hidden">Department</label>
                    <select class="form-select" id="application-department-filter" data-application-filter="departmentId"></select>
                </div>
                {{-- One date: shows applications received on exactly that day. Empty means no date restriction. --}}
                <div class="col-md-6 col-xl-3">
                    <div class="input-group">
                        <label class="input-group-text" for="application-applied-on-filter">Application Date</label>
                        <input type="date" class="form-control" id="application-applied-on-filter"
                            title="Show applications received on this date" data-application-filter="appliedOn">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-application-clear>Clear Filters</button>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 employees-table">
                    <thead>
                        <tr>
                            @foreach ($columns as $key => $label)
                                <th scope="col" aria-sort="none" data-sort-column="{{ $key }}">
                                    <button type="button" class="sort-button" data-sort-key="{{ $key }}">
                                        {{ $label }}
                                        <x-icon name="chevron-up" class="sort-icon sort-icon-ascending" />
                                        <x-icon name="chevron-down" class="sort-icon sort-icon-descending" />
                                    </button>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody data-application-rows></tbody>
                </table>
            </div>

            <div class="employees-empty d-none" data-application-empty>
                <strong>No applications found</strong>
                <span data-application-empty-hint>Add an application to get started.</span>
            </div>

            <div class="employees-footer d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <span data-application-summary></span>
                <nav aria-label="Application pages">
                    <ul class="pagination pagination-sm mb-0" data-application-pagination></ul>
                </nav>
            </div>
        </div>
    </div>

    <x-application-form-modal />
    <x-application-details-modal />
    <x-application-delete-modal />
@endsection
