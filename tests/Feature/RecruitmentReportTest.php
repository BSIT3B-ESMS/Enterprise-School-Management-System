<?php

beforeEach(fn () => $this->actingAs(hrHead()));

it('links Generate Report from the Recruitment page to its own page, beside Add Application', function () {
    $this->get(route('recruitment.index'))
        ->assertSeeInOrder([
            'data-report-link data-report-url="'.route('recruitment.report').'"', 'Generate Report',
            'data-application-add',
        ], escape: false);
});

it('lays the report out like the Employees report, with its own filters', function () {
    $this->get(route('recruitment.report'))
        ->assertOk()
        ->assertSeeInOrder([
            'Generate Report',
            'Export Format', 'value="pdf" autocomplete="off" checked>', 'value="xlsx"', 'value="csv"',
            'Applications to Include',
            '<label for="report-applicant-name" class="form-label">Applicant Name</label>', 'data-report-filter="name"',
            '<label for="report-department" class="form-label">Department</label>', 'data-report-filter="departmentId"',
            '<label for="report-job-position" class="form-label">Job Position</label>', 'data-report-filter="jobTitleId"',
            '<label for="report-status" class="form-label">Recruitment Status</label>', 'data-report-filter="status"',
            'Application Date', 'data-period-month', 'data-period-from', 'data-period-to', 'data-period-apply>Apply Filter',
            'data-report-clear>Clear Filters',
            'Report Preview', 'No applications match', 'data-report-preview',
            'Fields to Include', 'data-report-fields',
            'data-report-error', 'Back to Recruitment</a>', 'data-report-generate',
        ], escape: false);
});

it('keeps the Recruitment sidebar link current on the report page', function () {
    $this->get(route('recruitment.report'))
        ->assertSee('aria-current="page" href="'.route('recruitment.index').'"', escape: false);
});
