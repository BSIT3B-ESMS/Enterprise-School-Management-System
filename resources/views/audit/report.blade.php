@extends('layouts.admin')

@section('title', 'Generate Report · '.config('app.name'))

{{--
    Audit Logs Generate Report, laid out and generated like the Employees report (the shared sections in
    resources/views/components/report/). resources/js/audit-report.js lists the log entries matching the Date Range, User,
    Action, and Module chosen here, starting from the Audit Logs page's filters (carried by the Generate Report link);
    none of it changes the Audit Logs page's own filters. The log records no result or status for an entry, so there is
    no such filter.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="audit-report" data-export-url="{{ route('exports.store') }}">
        <div class="d-flex align-items-center gap-3">
            <a class="page-back" href="{{ route('audit.index') }}" aria-label="Back to audit logs" data-report-back>
                <x-icon name="back" />
            </a>
            <div>
                <h1 class="page-title">Generate Report</h1>
                <p class="text-body-secondary mt-1 mb-0">Export the audit log entries that match the filters you choose.</p>
            </div>
        </div>

        <x-report.format />

        <section class="card employee-form-card" aria-labelledby="report-filters-title">
            <h2 class="employee-form-section" id="report-filters-title">Entries to Include</h2>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="report-user" class="form-label">User</label>
                    <select class="form-select" id="report-user" data-report-filter="user"></select>
                </div>
                <div class="col-md-4">
                    <label for="report-action" class="form-label">Action</label>
                    <select class="form-select" id="report-action" data-report-filter="action"></select>
                </div>
                <div class="col-md-4">
                    <label for="report-module" class="form-label">Module</label>
                    <select class="form-select" id="report-module" data-report-filter="recordType"></select>
                </div>
            </div>

            <x-report.period id="report-period" legend="Date Range"
                help="Choose a month, then optionally a From Date and To Date inside it. With only a month, the whole month is included." />

            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mt-3">
                <p class="form-text mb-0">Leave everything on “All” to include every entry. These choices apply to this report only.</p>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-report-clear>Clear Filters</button>
            </div>
        </section>

        <x-report.preview empty-title="No entries match"
            empty-text="No audit log entries match the selected filters, so there is nothing to export. Try a wider date range or clear the filters." />

        <x-report.fields />

        <x-report.actions :back-url="route('audit.index')" back-label="Back to Audit Logs" />
    </div>
@endsection
