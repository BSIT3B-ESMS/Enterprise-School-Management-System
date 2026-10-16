@extends('layouts.admin')

@section('title', 'Generate Report · '.config('app.name'))

{{--
    Generate Report. The rows are the Employees table's rows: resources/js/employee-report.js reads the
    table's search, Date Hired, department, job title, and sort from this page's query string (carried over
    by the Generate Report link) and filters them with resources/js/employee-list.js. The fields chosen here
    only pick the columns; they never change the Employees filters.
    Layout, top to bottom: Export Format, Report Preview, Fields to Include, then the actions.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="employee-report" data-employees-url="{{ route('employees.index') }}">
        <div class="d-flex align-items-center gap-3">
            <a class="page-back" href="{{ route('employees.index') }}" aria-label="Back to employees" data-report-back>
                <x-icon name="back" />
            </a>
            <div>
                <h1 class="page-title">Generate Report</h1>
                <p class="text-body-secondary mt-1 mb-0">Export the employees currently shown on the Employees page.</p>
            </div>
        </div>

        <section class="card employee-form-card" aria-labelledby="report-format-title">
            <h2 class="employee-form-section" id="report-format-title">
                Export Format <small>Selected: <strong data-report-format-label>PDF</strong></small>
            </h2>

            <div class="btn-group w-100 report-formats" role="radiogroup" aria-labelledby="report-format-title">
                <input type="radio" class="btn-check" name="reportFormat" id="report-format-pdf" value="pdf" autocomplete="off" checked>
                <label class="btn btn-outline-primary" for="report-format-pdf">PDF</label>

                <input type="radio" class="btn-check" name="reportFormat" id="report-format-xlsx" value="xlsx" autocomplete="off">
                <label class="btn btn-outline-primary" for="report-format-xlsx">Excel (.xlsx)</label>

                <input type="radio" class="btn-check" name="reportFormat" id="report-format-csv" value="csv" autocomplete="off">
                <label class="btn btn-outline-primary" for="report-format-csv">CSV</label>
            </div>
            <p class="form-text mb-0 mt-2" data-report-format-hint></p>
        </section>

        <section class="card employee-form-card" aria-labelledby="report-preview-title">
            <h2 class="employee-form-section" id="report-preview-title">Report Preview</h2>

            <p class="mb-1 fw-medium" data-report-count></p>
            <ul class="small text-body-secondary mb-2 ps-3" data-report-criteria></ul>
            <p class="small mb-3">
                To change which employees are included,
                <a href="{{ route('employees.index') }}" data-report-back>change the search or filters on the Employees page</a>.
            </p>

            <div class="employees-empty border rounded d-none" data-report-empty>
                <strong>No employees match</strong>
                No employees match the current search and filters, so there is nothing to export.
            </div>

            <div class="d-none" data-report-preview>
                <div class="table-responsive border rounded">
                    <table class="table table-sm align-middle mb-0 report-preview-table">
                        <thead data-report-preview-head></thead>
                        <tbody data-report-preview-rows></tbody>
                    </table>
                </div>
                <p class="form-text mb-0" data-report-preview-note></p>
            </div>
        </section>

        <section class="card employee-form-card" aria-labelledby="report-fields-title">
            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
                <h2 class="employee-form-section mb-0" id="report-fields-title">
                    Fields to Include <small data-report-field-count></small>
                </h2>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-report-select-all>Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-report-deselect-all>Deselect All</button>
                </div>
            </div>

            <p class="form-text mt-0">Columns appear in the order listed. Missing values are left blank.</p>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" data-report-fields></div>

            <div class="alert alert-warning py-2 mt-3 mb-0 d-none" role="status" data-report-fields-error>
                Select at least one field to include in the report.
            </div>
        </section>

        <div class="alert alert-danger mb-0 d-none" role="alert" data-report-error></div>

        <div class="d-flex justify-content-end gap-2 flex-wrap">
            <a class="btn btn-outline-secondary" href="{{ route('employees.index') }}" data-report-back>Back to Employees</a>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-report-generate>
                <span class="spinner-border spinner-border-sm d-none" aria-hidden="true" data-report-spinner></span>
                <span data-report-generate-label role="status">Generate PDF</span>
            </button>
        </div>
    </div>
@endsection
