@extends('layouts.admin')

@section('title', 'Employees · '.config('app.name'))

@php
    $columns = [
        'id' => 'Employee ID',
        'name' => 'Name',
        'department' => 'College',
        'jobTitle' => 'Job Title',
        'salary' => 'Base Salary',
        'hiredOn' => 'Date Hired',
    ];
@endphp

{{--
    Rows, filter options, sorting, and pagination are rendered by resources/js/employees.js from resources/js/mock-data.js.
    Date Hired filters by hiring date only: a month, optionally narrowed to a From Date and To Date inside it, applied with
    Apply Filter. Archived Employees (lower right) lists employees HR has archived; deleting is only possible there.
    Clicking a row (or its name) opens the employee's full-screen profile, carrying the table's current
    search, filters, sort, and page so Back to Employees restores them.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="employees" data-profile-base="{{ url('/employees') }}">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <h1 class="page-title">Employees</h1>

            <div class="d-flex gap-2 flex-wrap">
                {{-- employees.js appends the table's current search, filters, sort, and page to this link. --}}
                <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('employees.report') }}"
                    data-employee-report-link data-report-url="{{ route('employees.report') }}">
                    <x-icon name="report" />
                    Generate Report
                </a>
                <a class="btn btn-primary d-inline-flex align-items-center gap-2" href="{{ route('employees.create') }}">
                    <x-icon name="plus" />
                    Add Employee
                </a>
            </div>
        </div>

        <div class="card employees-filters">
            <div class="row g-3">
                <div class="col-md-6 col-xl-4">
                    <label for="employee-search" class="visually-hidden">Search employees</label>
                    <div class="search-field">
                        <x-icon name="search" />
                        <input type="search" class="form-control" id="employee-search" placeholder="Search employees..."
                            autocomplete="off" data-employee-search>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <label for="employee-job-title-filter" class="visually-hidden">Job title</label>
                    <select class="form-select" id="employee-job-title-filter" data-employee-filter="jobTitleId"></select>
                </div>
                <div class="col-md-6 col-xl-4">
                    <label for="employee-department-filter" class="visually-hidden">College</label>
                    <select class="form-select" id="employee-department-filter" data-employee-filter="departmentId"></select>
                </div>
            </div>

            {{-- Date Hired: the month sets the period under review; From Date and To Date pick the exact range inside it. --}}
            <fieldset class="mt-3 pt-3 border-top" data-hired-filter>
                <legend class="form-label small fw-semibold mb-2">Date Hired</legend>

                <div class="row g-3 align-items-start">
                    <div class="col-md-6 col-xl-3">
                        <label for="employee-hired-month" class="form-label">Month</label>
                        <input type="month" class="form-control" id="employee-hired-month" placeholder="YYYY-MM"
                            aria-describedby="employee-hired-month-error" data-hired-month>
                        <div class="invalid-feedback" id="employee-hired-month-error" data-hired-error="month"></div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="employee-hired-from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="employee-hired-from"
                            aria-describedby="employee-hired-from-error" data-hired-from>
                        <div class="invalid-feedback" id="employee-hired-from-error" data-hired-error="from"></div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label for="employee-hired-to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="employee-hired-to"
                            aria-describedby="employee-hired-to-error" data-hired-to>
                        <div class="invalid-feedback" id="employee-hired-to-error" data-hired-error="to"></div>
                    </div>
                    <div class="col-md-6 col-xl-3 d-flex gap-2 align-self-xl-end">
                        <button type="button" class="btn btn-primary" data-hired-apply>Apply Filter</button>
                        <button type="button" class="btn btn-outline-secondary" data-hired-clear>Clear Filter</button>
                    </div>
                </div>

                <p class="form-text mb-0 mt-2">
                    Choose a month, then optionally a From Date and To Date inside it. With only a month, the whole month is shown.
                </p>
            </fieldset>

            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mt-3">
                <span class="ems-chip d-none" data-hired-summary></span>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" data-employee-clear>Clear Filters</button>
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
                    <tbody data-employee-rows></tbody>
                </table>
            </div>

            <div class="employees-empty d-none" data-employee-empty>
                <strong>No employees match</strong>
                Try a different search or clear the filters.
            </div>

            <div class="employees-footer d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <span data-employee-summary></span>
                <nav aria-label="Employee pages">
                    <ul class="pagination pagination-sm mb-0" data-employee-pagination></ul>
                </nav>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('employees.archived') }}"
                data-archived-link>
                <x-icon name="folder" />
                Archived Employees
                <span class="ems-chip tabular-nums" data-archived-count>0</span>
            </a>
        </div>
    </div>
@endsection
