@extends('layouts.admin')

@section('title', 'Colleges · '.config('app.name'))

@php
    $columns = [
        'name' => 'College Name',
        'headcount' => 'No. of Faculty',
    ];
@endphp

{{--
    Rows, sorting, and pagination are rendered by resources/js/departments.js from resources/js/mock-data.js.
    Clicking a row (or pressing Enter/Space on it) opens the college details modal, which offers Edit and Delete. Only the
    approved colleges can be listed (resources/js/mock-data.js approvedColleges), so Add College offers the ones missing.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="departments" data-employees-url="{{ route('employees.index') }}">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Colleges</h1>
                <p class="text-body-secondary mt-1 mb-0">The university’s colleges and their faculty distribution</p>
            </div>

            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal"
                data-bs-target="#department-form-modal" data-department-add>
                <x-icon name="plus" />
                Add College
            </button>
        </div>

        <div class="departments-search">
            <label for="department-search" class="visually-hidden">Search colleges</label>
            <div class="search-field">
                <x-icon name="search" />
                <input type="search" class="form-control" id="department-search" placeholder="Search colleges..."
                    autocomplete="off" data-department-search>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 employees-table departments-table">
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
                    <tbody data-department-rows></tbody>
                </table>
            </div>

            <div class="employees-empty d-none" data-department-empty>
                <strong>No colleges found</strong>
                <span data-department-empty-hint>Add a college to get started.</span>
            </div>

            <div class="employees-footer d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <span data-department-summary></span>
                <nav aria-label="College pages">
                    <ul class="pagination pagination-sm mb-0" data-department-pagination></ul>
                </nav>
            </div>
        </div>
    </div>

    <x-department-form-modal />
    <x-department-details-modal />
    <x-department-delete-modal />
@endsection
