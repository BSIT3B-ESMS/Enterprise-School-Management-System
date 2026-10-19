@extends('layouts.admin')

@section('title', 'Audit Logs · '.config('app.name'))

@php
    $columns = [
        'at' => 'Date and Time',
        'user' => 'User',
        'action' => 'Action',
        'entity' => 'Affected Record',
    ];
@endphp

{{--
    Rows, filter options, sorting, and pagination are rendered by resources/js/audit-logs.js from resources/js/mock-data.js.
    Entries are read-only: clicking a row (or pressing Enter/Space on it) opens the entry details modal.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="audit">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="page-title">Audit Logs</h1>
                <p class="text-body-secondary mt-1 mb-0">Every sign-in and data change, newest first.</p>
            </div>

            <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('audit.report') }}"
                data-report-link data-report-url="{{ route('audit.report') }}">
                <x-icon name="report" />
                Generate Report
            </a>
        </div>

        <div class="card employees-filters">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="audit-search" class="visually-hidden">Search activity</label>
                    <div class="search-field">
                        <x-icon name="search" />
                        <input type="search" class="form-control" id="audit-search" placeholder="Search activity..."
                            autocomplete="off" data-audit-search>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label for="audit-action-filter" class="visually-hidden">Action</label>
                    <select class="form-select" id="audit-action-filter" data-audit-filter="action"></select>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label for="audit-record-filter" class="visually-hidden">Record type</label>
                    <select class="form-select" id="audit-record-filter" data-audit-filter="recordType"></select>
                </div>
                {{-- One date: shows entries recorded on exactly that day. Empty means no date restriction. --}}
                <div class="col-md-6 col-xl-4">
                    <div class="input-group">
                        <label class="input-group-text" for="audit-date-filter">Date</label>
                        <input type="date" class="form-control" id="audit-date-filter"
                            title="Show entries recorded on this date" data-audit-filter="date">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-audit-clear>Clear Filters</button>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 employees-table audit-table">
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
                            <th scope="col">Details</th>
                        </tr>
                    </thead>
                    <tbody data-audit-rows></tbody>
                </table>
            </div>

            <div class="employees-empty d-none" data-audit-empty>
                <strong>No activity found</strong>
                Nothing matches the current filters. Try a different search or clear the filters.
            </div>

            <div class="employees-footer d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <span data-audit-summary></span>
                <nav aria-label="Audit log pages">
                    <ul class="pagination pagination-sm mb-0" data-audit-pagination></ul>
                </nav>
            </div>
        </div>
    </div>

    <x-audit-details-modal />
@endsection
