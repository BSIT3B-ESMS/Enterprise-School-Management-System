@extends('layouts.admin')

@section('title', 'Departments · '.config('app.name'))

@php
    $columns = [
        'name' => 'Department Name',
        'headcount' => 'No. of Faculty',
    ];
@endphp

{{--
    Rows, sorting, and pagination are rendered by resources/js/departments.js from resources/js/mock-data.js.
    Clicking a row (or pressing Enter/Space on it) opens the department details modal, which offers Edit and Delete.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="departments" data-employees-url="{{ route('employees.index') }}">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Departments</h1>
                <p class="text-body-secondary mt-1 mb-0">Manage academic units, colleges, and faculty distribution</p>
            </div>

            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal"
                data-bs-target="#department-form-modal" data-department-add>
                <x-icon name="plus" />
                Add Department
            </button>
        </div>

        <div class="departments-search">
            <label for="department-search" class="visually-hidden">Search departments</label>
            <div class="search-field">
                <x-icon name="search" />
                <input type="search" class="form-control" id="department-search" placeholder="Search departments..."
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
                <strong>No departments found</strong>
                <span data-department-empty-hint>Add a department to get started.</span>
            </div>

            <div class="employees-footer d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <span data-department-summary></span>
                <nav aria-label="Department pages">
                    <ul class="pagination pagination-sm mb-0" data-department-pagination></ul>
                </nav>
            </div>
        </div>
    </div>

    <x-department-form-modal />
    <x-department-details-modal />
    <x-department-delete-modal />
@endsection
