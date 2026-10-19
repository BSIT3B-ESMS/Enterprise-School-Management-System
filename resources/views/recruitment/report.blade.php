@extends('layouts.admin')

@section('title', 'Generate Report · '.config('app.name'))

{{--
    Recruitment Generate Report, laid out and generated like the Employees report (the shared sections in
    resources/views/components/report/). resources/js/recruitment-report.js lists the applications matching the
    Application Date range, Applicant Name, Job Position, Department, and Status chosen here, starting from the
    Recruitment page's filters (carried by the Generate Report link); none of it changes the Recruitment page's own filters.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="recruitment-report">
        <div class="d-flex align-items-center gap-3">
            <a class="page-back" href="{{ route('recruitment.index') }}" aria-label="Back to recruitment" data-report-back>
                <x-icon name="back" />
            </a>
            <div>
                <h1 class="page-title">Generate Report</h1>
                <p class="text-body-secondary mt-1 mb-0">Export the applications that match the filters you choose.</p>
            </div>
        </div>

        <x-report.format />

        <section class="card employee-form-card" aria-labelledby="report-filters-title">
            <h2 class="employee-form-section" id="report-filters-title">Applications to Include</h2>

            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <label for="report-applicant-name" class="form-label">Applicant Name</label>
                    <input type="search" class="form-control" id="report-applicant-name" placeholder="Any applicant"
                        autocomplete="off" data-report-filter="name">
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="report-department" class="form-label">Department</label>
                    <select class="form-select" id="report-department" data-report-filter="departmentId"></select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="report-job-position" class="form-label">Job Position</label>
                    <select class="form-select" id="report-job-position" data-report-filter="jobTitleId"></select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="report-status" class="form-label">Recruitment Status</label>
                    <select class="form-select" id="report-status" data-report-filter="status"></select>
                </div>
            </div>

            <x-report.period id="report-period" legend="Application Date"
                help="Choose a month, then optionally a From Date and To Date inside it. With only a month, the whole month is included." />

            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mt-3">
                <p class="form-text mb-0">Leave everything empty or on “All” to include every application. These choices apply to this report only.</p>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-report-clear>Clear Filters</button>
            </div>
        </section>

        <x-report.preview empty-title="No applications match"
            empty-text="No applications match the selected filters, so there is nothing to export. Try a wider date range or clear the filters." />

        <x-report.fields />

        <x-report.actions :back-url="route('recruitment.index')" back-label="Back to Recruitment" />
    </div>
@endsection
