@extends('layouts.admin')

@section('title', 'Archived Employees · '.config('app.name'))

@php
    $columns = ['Employee ID', 'Name', 'College', 'Job Title', 'Date Hired', 'Archived On'];
@endphp

{{--
    Archived Employees: the employees HR archived from the Employees page, rendered by resources/js/archived-employees.js
    from the same mock data. Their information is kept as it was. A row opens the employee's details, where Restore returns
    them to the Employees list and Delete (the only place an employee can be deleted) removes the record for good after
    a confirmation.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="archived-employees">
        <div class="d-flex align-items-center gap-3">
            <a class="page-back" href="{{ route('employees.index') }}" aria-label="Back to employees">
                <x-icon name="back" />
            </a>
            <div>
                <h1 class="page-title">Archived Employees</h1>
                <p class="text-body-secondary mt-1 mb-0">Archived employees are kept here, apart from the active Employees list.</p>
            </div>
        </div>

        <div class="card employees-filters">
            <label for="archived-search" class="visually-hidden">Search archived employees</label>
            <div class="search-field">
                <x-icon name="search" />
                <input type="search" class="form-control" id="archived-search" placeholder="Search archived employees..."
                    autocomplete="off" data-archived-search>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 employees-table">
                    <thead>
                        <tr>
                            @foreach ($columns as $label)
                                <th scope="col">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody data-archived-rows></tbody>
                </table>
            </div>

            <div class="employees-empty d-none" data-archived-empty>
                <strong data-archived-empty-title>No archived employees</strong>
                <span data-archived-empty-hint>Employees you archive from their profile appear here.</span>
            </div>

            <div class="employees-footer d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <span data-archived-summary></span>
                <nav aria-label="Archived employee pages">
                    <ul class="pagination pagination-sm mb-0" data-archived-pagination></ul>
                </nav>
            </div>
        </div>
    </div>

    <x-archived-employee-details-modal />
    <x-employee-delete-modal />
@endsection
