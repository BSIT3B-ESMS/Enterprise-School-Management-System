@extends('layouts.admin')

@section('title', 'Employees · '.config('app.name'))

@php
    $columns = [
        'id' => 'Employee ID',
        'name' => 'Name',
        'department' => 'Department',
        'jobTitle' => 'Job Title',
        'hiredOn' => 'Date Hired',
    ];
@endphp

{{--
    Rows, filter options, sorting, and pagination are rendered by resources/js/employees.js from resources/js/mock-data.js.
    Clicking a row (or pressing Enter/Space on it) opens the employee details modal.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="employees">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <h1 class="page-title">Employees</h1>

            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2"
                    data-copy-csv>
                    <x-icon name="copy" />
                    Copy CSV
                </button>
                <a class="btn btn-primary d-inline-flex align-items-center gap-2" href="{{ route('employees.create') }}">
                    <x-icon name="plus" />
                    Add Employee
                </a>
            </div>
        </div>

        <div class="card employees-filters">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="employee-search" class="visually-hidden">Search employees</label>
                    <div class="search-field">
                        <x-icon name="search" />
                        <input type="search" class="form-control" id="employee-search" placeholder="Search employees..."
                            autocomplete="off" data-employee-search>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="employee-job-title-filter" class="visually-hidden">Job title</label>
                    <select class="form-select" id="employee-job-title-filter" data-employee-filter="jobTitleId"></select>
                </div>
                <div class="col-md-4">
                    <label for="employee-department-filter" class="visually-hidden">Department</label>
                    <select class="form-select" id="employee-department-filter" data-employee-filter="departmentId"></select>
                </div>
                <x-date-range-filters name="employee" subject="Date Hired" />
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
    </div>

    <x-employee-form-modal />
    <x-employee-details-modal />
    <x-employee-delete-modal />
@endsection
