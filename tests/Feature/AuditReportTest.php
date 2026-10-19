<?php

it('links Generate Report from the Audit Logs page to its own page', function () {
    $this->get(route('audit.index'))
        ->assertSee('href="'.route('audit.report').'"', escape: false)
        ->assertSee('data-report-link data-report-url="'.route('audit.report').'"', escape: false);
});

it('lays the report out like the Employees report, with its own filters', function () {
    $this->get(route('audit.report'))
        ->assertOk()
        ->assertSeeInOrder([
            'Generate Report',
            'Export Format', 'value="pdf" autocomplete="off" checked>', 'value="xlsx"', 'value="csv"',
            'Entries to Include',
            '<label for="report-user" class="form-label">User</label>', 'data-report-filter="user"',
            '<label for="report-action" class="form-label">Action</label>', 'data-report-filter="action"',
            '<label for="report-module" class="form-label">Module</label>', 'data-report-filter="recordType"',
            'Date Range', 'data-period-month', 'data-period-from', 'data-period-to', 'data-period-apply>Apply Filter',
            'data-report-clear>Clear Filters',
            'Report Preview', 'No entries match', 'data-report-preview',
            'Fields to Include', 'data-report-fields',
            'data-report-error', 'Back to Audit Logs</a>', 'data-report-generate',
        ], escape: false);
});

it('keeps the Audit Logs sidebar link current on the report page', function () {
    $this->get(route('audit.report'))
        ->assertSee('aria-current="page" href="'.route('audit.index').'"', escape: false);
});
