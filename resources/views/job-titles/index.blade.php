@extends('layouts.admin')

@section('title', 'Job Titles · '.config('app.name'))

@php
    $columns = [
        'name' => 'Job Title',
        'department' => 'Department',
        'salary' => 'Base Salary Range',
        'benefits' => 'Benefits',
    ];
@endphp

{{--
    Rows, filter options, sorting, and pagination are rendered by resources/js/job-titles.js from resources/js/mock-data.js.
    Clicking a row (or pressing Enter/Space on it) opens the job title details modal, which offers Edit and Delete.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="job-titles" data-employees-url="{{ route('employees.index') }}">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Job Titles</h1>
                <p class="text-body-secondary mt-1 mb-0">
                    Manage and organize academic ranks and faculty titles across collegiate departments.
                </p>
            </div>

            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal"
                data-bs-target="#job-title-form-modal" data-job-title-add>
                <x-icon name="plus" />
                Add Job Title
            </button>
        </div>

        <div class="card employees-filters">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="job-title-search" class="visually-hidden">Search job titles</label>
                    <div class="search-field">
                        <x-icon name="search" />
                        <input type="search" class="form-control" id="job-title-search" placeholder="Search job titles..."
                            autocomplete="off" data-job-title-search>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="job-title-department-filter" class="visually-hidden">Department</label>
                    <select class="form-select" id="job-title-department-filter" data-job-title-filter="departmentId"></select>
                </div>
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
                    <tbody data-job-title-rows></tbody>
                </table>
            </div>

            <div class="employees-empty d-none" data-job-title-empty>
                <strong>No job titles found</strong>
                <span data-job-title-empty-hint>Add a job title to get started.</span>
            </div>

            <div class="employees-footer d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <span data-job-title-summary></span>
                <nav aria-label="Job title pages">
                    <ul class="pagination pagination-sm mb-0" data-job-title-pagination></ul>
                </nav>
            </div>
        </div>
    </div>

    <x-job-title-form-modal />
    <x-job-title-details-modal />
    <x-job-title-delete-modal />
@endsection
