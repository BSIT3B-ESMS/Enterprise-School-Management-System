@extends('layouts.admin')

@section('title', 'Generate Report · '.config('app.name'))

{{--
    Generate Report. The rows are the Employees table's rows: resources/js/employee-report.js reads the
    table's search, Date Hired, department, job title, and sort from this page's query string (carried over
    by the Generate Report link) and filters them with resources/js/employee-list.js. Employees to Include adds a
    Department and a Job Title choice for this report (starting from the Employees filters), and the fields chosen
    pick the columns; none of it changes the Employees page's own filters.
    Layout, top to bottom: Export Format, Employees to Include, Report Preview, Fields to Include, then the actions (the
    shared sections in resources/views/components/report/, as on the Recruitment and Audit Logs reports).
--}}
@section('content')
    <div class="d-grid gap-4" data-page="employee-report" data-employees-url="{{ route('employees.index') }}">
        <div class="d-flex align-items-center gap-3">
            <a class="page-back" href="{{ route('employees.index') }}" aria-label="Back to employees" data-report-back>
                <x-icon name="back" />
            </a>
            <div>
                <h1 class="page-title">Generate Report</h1>
                <p class="text-body-secondary mt-1 mb-0">Export the employees shown on the Employees page, or narrow them by department and job title.</p>
            </div>
        </div>

        <x-report.format />

        <section class="card employee-form-card" aria-labelledby="report-filters-title">
            <h2 class="employee-form-section" id="report-filters-title">Employees to Include</h2>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="report-department" class="form-label">Department</label>
                    <select class="form-select" id="report-department" data-report-filter="departmentId"></select>
                </div>
                <div class="col-md-6">
                    <label for="report-job-title" class="form-label">Job Title</label>
                    <select class="form-select" id="report-job-title" data-report-filter="jobTitleId"></select>
                </div>
            </div>
            <p class="form-text mb-0 mt-2">
                Leave both on “All” to include every department and job title, or choose a department and/or a job title.
                These choices apply to this report only.
            </p>
        </section>

        <x-report.preview empty-title="No employees match"
            empty-text="No employees match the current search and filters, so there is nothing to export.">
            To change which employees are included, use the filters above or
            <a href="{{ route('employees.index') }}" data-report-back>change the search or filters on the Employees page</a>.
        </x-report.preview>

        <x-report.fields />

        <x-report.actions :back-url="route('employees.index')" back-label="Back to Employees" />
    </div>
@endsection
