@extends('layouts.admin')

@section('title', 'Open Job Positions · '.config('app.name'))

@php
    $columns = [
        'name' => 'Position',
        'department' => 'Department',
        'employmentType' => 'Employment Type',
        'salary' => 'Base Salary Range',
        'openings' => 'Openings',
        'status' => 'Status',
    ];
@endphp

{{--
    The job postings, rendered by resources/js/job-positions.js from the same job title records the Job Titles and
    Recruitment pages and the public Careers website read. A posting is open while it has openings, and only open
    postings appear on the Careers website. Clicking a row (or pressing Enter/Space on it) opens the posting's details,
    which offer Edit, Open or Close, and Remove (a posting that employees or applications still use cannot be removed;
    close it instead). Add Job Posting is in the page header. The filters (search, department, employment type, status)
    combine, and Clear Filters resets them all; the employment type options come from the job data.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="job-positions" data-careers-url="{{ route('careers.index') }}">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Open Job Positions</h1>
                <p class="text-body-secondary mt-1 mb-0">
                    Manage the job postings. Open postings are listed on the public Careers website.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-secondary" href="{{ route('careers.index') }}" target="_blank" rel="noopener">View Careers Website</a>
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal"
                    data-bs-target="#job-position-form-modal" data-job-position-add>
                    <x-icon name="plus" />
                    Add Job Posting
                </button>
            </div>
        </div>

        <div class="card employees-filters">
            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <label for="job-position-search" class="visually-hidden">Search job postings</label>
                    <div class="search-field">
                        <x-icon name="search" />
                        <input type="search" class="form-control" id="job-position-search" placeholder="Search job postings..."
                            autocomplete="off" data-job-position-search>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="job-position-department-filter" class="visually-hidden">Department</label>
                    <select class="form-select" id="job-position-department-filter" data-job-position-filter="departmentId"></select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="job-position-type-filter" class="visually-hidden">Employment type</label>
                    <select class="form-select" id="job-position-type-filter" data-job-position-filter="employmentType"></select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="job-position-status-filter" class="visually-hidden">Status</label>
                    <select class="form-select" id="job-position-status-filter" data-job-position-filter="status">
                        <option value="">All Statuses</option>
                        <option value="Open">Open</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-job-position-clear>Clear Filters</button>
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
                    <tbody data-job-position-rows></tbody>
                </table>
            </div>

            <div class="employees-empty d-none" data-job-position-empty>
                <strong>No job postings found</strong>
                <span data-job-position-empty-hint>Add a job posting to get started.</span>
            </div>

            <div class="employees-footer d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <span data-job-position-summary></span>
                <nav aria-label="Job posting pages">
                    <ul class="pagination pagination-sm mb-0" data-job-position-pagination></ul>
                </nav>
            </div>
        </div>
    </div>

    <x-job-position-form-modal />
    <x-job-position-details-modal />
    <x-job-position-remove-modal />
@endsection
