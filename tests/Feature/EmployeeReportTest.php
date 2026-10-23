<?php

it('links generate report to its own page instead of a modal', function () {
    $this->get(route('employees.index'))
        ->assertSee('href="'.route('employees.report').'"', escape: false)
        ->assertSee('data-employee-report-link data-report-url="'.route('employees.report').'"', escape: false)
        ->assertDontSee('employee-report-modal', escape: false);
});

it('renders the report page with a title and a way back to employees', function () {
    $this->get(route('employees.report'))
        ->assertSee('Generate Report')
        ->assertSee('aria-label="Back to employees"', escape: false)
        ->assertSee('Back to Employees</a>', escape: false)
        ->assertSee('data-employees-url="'.route('employees.index').'"', escape: false);
});

it('offers pdf, excel, and csv with pdf selected by default', function () {
    $this->get(route('employees.report'))
        ->assertSeeInOrder([
            'Export Format', 'Selected:', 'data-report-format-label>PDF',
            'value="pdf" autocomplete="off" checked>', 'PDF</label>',
            'value="xlsx" autocomplete="off">', 'Excel (.xlsx)</label>',
            'value="csv" autocomplete="off">', 'CSV</label>',
        ], escape: false);
});

it('stacks export format, then the preview, then the fields, then the actions', function () {
    $this->get(route('employees.report'))
        ->assertSeeInOrder([
            'Export Format', 'value="pdf"', 'value="xlsx"', 'value="csv"',
            'Report Preview', 'data-report-count', 'No employees match', 'data-report-preview',
            'Fields to Include', 'data-report-select-all>Select All', 'data-report-deselect-all>Deselect All',
            'data-report-fields', 'Select at least one field to include in the report.',
            'data-report-error', 'Back to Employees</a>', 'data-report-generate',
        ], escape: false);
});

it('lays the export formats out as one full-width row', function () {
    $this->get(route('employees.report'))
        ->assertSee('class="btn-group w-100 report-formats"', escape: false)
        ->assertDontSee('col-lg-5', escape: false);
});

it('does not add its own filters, linking back to the employees filters instead', function () {
    $this->get(route('employees.report'))
        ->assertSee('change the search or filters on the Employees page', escape: false)
        ->assertDontSee('data-employee-filter', escape: false)
        ->assertDontSee('type="date"', escape: false);
});

it('keeps the employees sidebar link current on the report page', function () {
    $this->get(route('employees.report'))
        ->assertSee('aria-current="page" href="'.route('employees.index').'"', escape: false);
});

it('lets HR choose a college and job title for the report, separate from the Employees filters', function () {
    $this->get(route('employees.report'))
        ->assertSeeInOrder([
            'Export Format',
            'Employees to Include',
            '<label for="report-department" class="form-label">College</label>', 'data-report-filter="departmentId"',
            '<label for="report-job-title" class="form-label">Job Title</label>', 'data-report-filter="jobTitleId"',
            'Leave both on “All” to include every college and job title',
            'Report Preview',
        ], escape: false);
});
