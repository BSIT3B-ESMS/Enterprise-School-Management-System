@extends('layouts.admin')

@section('title', 'Generate Report · '.config('app.name'))

{{--
    Generate Report (HR Admin only). The server sends the employees that match the Employees page's search, tab, and filters
    (data-rows); resources/js/employee-report.js lets the HR Admin narrow them by college, office, and job title, pick the
    fields, and builds the PDF, Excel, or CSV file in the browser. Government IDs are last four digits only, and every
    export is written to the audit log first (resources/js/export-log.js).
--}}
@section('content')
    <div class="d-grid gap-4" data-page="employee-report" data-employees-url="{{ route('employees.index') }}"
        data-export-url="{{ route('exports.store') }}" data-rows="{{ json_encode($rows) }}" data-summary="{{ json_encode($summary) }}">
        <div class="d-flex align-items-center gap-3">
            <a class="page-back" href="{{ $backUrl }}" aria-label="Back to employees" data-report-back>
                <x-icon name="back" />
            </a>
            <div>
                <h1 class="page-title">Generate Report</h1>
                <p class="text-body-secondary mt-1 mb-0">Export the employees shown on the Employees page, or narrow them by college, office, and job title.</p>
            </div>
        </div>

        <x-report.format />

        <section class="card employee-form-card" aria-labelledby="report-filters-title">
            <h2 class="employee-form-section" id="report-filters-title">Employees to Include</h2>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="report-college" class="form-label">College</label>
                    <select class="form-select" id="report-college" data-report-filter="college">
                        <option value="">All colleges</option>
                        @foreach ($colleges as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="report-office" class="form-label">Office</label>
                    <select class="form-select" id="report-office" data-report-filter="office">
                        <option value="">All offices</option>
                        @foreach ($offices as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="report-job-title" class="form-label">Job Title</label>
                    <select class="form-select" id="report-job-title" data-report-filter="jobTitle">
                        <option value="">All job titles</option>
                        @foreach ($jobTitles as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="form-text mb-0 mt-2">
                These choices narrow this report only. They do not change the Employees page.
            </p>
        </section>

        <x-report.preview empty-title="No employees match"
            empty-text="No employees match the current search and filters, so there is nothing to export.">
            To change which employees are included, use the filters above or
            <a href="{{ $backUrl }}" data-report-back>change the search or filters on the Employees page</a>.
        </x-report.preview>

        <x-report.fields />

        <x-report.actions :back-url="$backUrl" back-label="Back to Employees" />
    </div>
@endsection
