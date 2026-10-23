<?php

it('serves the employee calendar with the sidebar Calendar item current', function () {
    $this->get(route('employee.calendar'))
        ->assertOk()
        ->assertSee('data-page="calendar" data-calendar-scope="own"', escape: false)
        ->assertSee('aria-current="page" href="'.route('employee.calendar').'"', escape: false);
});

it('offers only the leave statuses, all selected, with clear filters, a legend, and no event type filter', function () {
    $this->get(route('employee.calendar'))
        ->assertSeeInOrder([
            'Leave Status',
            'value="Pending"', 'checked data-calendar-status-filter', 'value="Approved"',
            'data-calendar-clear>Clear Filters</button>',
            'data-calendar-legend',
        ], escape: false)
        ->assertDontSee('value="Filed"', escape: false)
        ->assertDontSee('value="Scheduled"', escape: false)
        ->assertDontSee('value="Completed"', escape: false)
        ->assertDontSee('data-calendar-type-filter', escape: false)
        ->assertDontSee('Applicant', escape: false);
});

it('reuses the calendar month navigation, grid, day panel, and details', function () {
    $this->get(route('employee.calendar'))
        ->assertSeeInOrder([
            'data-calendar-month-title',
            'aria-label="Previous month"', 'data-calendar-today>Today</button>', 'aria-label="Next month"',
            'data-calendar-month-empty',
            'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat',
            'data-calendar-grid',
            'data-calendar-day-title', 'data-calendar-day-events', 'data-calendar-day-empty',
            'Leave Details', 'data-calendar-details', 'Select a leave to view its details.',
        ], escape: false);
});

it('has no HR actions or links on the employee calendar', function () {
    $this->get(route('employee.calendar'))
        ->assertDontSee('data-calendar-approve', escape: false)
        ->assertDontSee('data-employees-url', escape: false)
        ->assertDontSee('data-recruitment-url', escape: false)
        ->assertDontSee('href="'.route('employees.index').'"', escape: false);
});

it('lets the employee request leave from the Leave Request page, as a Pending request for HR to approve', function () {
    $this->get(route('employee.calendar', ['view' => 'leave-requests']))
        ->assertSeeInOrder([
            'id="my-leave-requests"', 'data-bs-target="#leave-request-modal"', 'Request Leave',
            'data-leave-balance', 'data-leave-balance-field="credits"', 'data-leave-balance-field="used"', 'data-leave-balance-field="remaining"',
            'data-leave-search', 'data-leave-rows',
            'id="leave-request-modal"', 'data-leave-request-form', 'data-leave-credit-note',
            'data-leave-employee="name"', 'data-leave-employee="id"', 'data-leave-employee="department"', 'data-leave-employee="jobTitle"',
            'for="leave-type"', 'Leave Type', 'name="type"', 'data-leave-type-options',
            'for="leave-start"', 'Start Date', 'name="startDate"',
            'for="leave-end"', 'End Date', 'name="endDate"',
            'for="leave-reason"', 'Reason', 'name="reason"',
            'data-document-field="supportingDocuments"', 'Supporting Documents (optional)',
            'Your request is Pending until',
            'Submit Request',
        ], escape: false)
        ->assertDontSee('First Day')
        ->assertDontSee('data-calendar-approve', escape: false);
});

it('lists the employee\'s own leave requests on a separate Leave Request page, with cancel but no approve or reject', function () {
    $leaveUrl = route('employee.calendar', ['view' => 'leave-requests']);
    $html = $this->get($leaveUrl)->assertOk()->getContent();

    $this->get(route('employee.calendar'))
        ->assertDontSee('Request Leave', escape: false)
        ->assertDontSee('data-bs-target="#leave-request-modal"', escape: false);

    $this->get($leaveUrl)
        ->assertDontSee('data-page="calendar"', escape: false)
        ->assertDontSee('data-calendar-grid', escape: false)
        ->assertSeeInOrder([
            'id="my-leave-requests"', 'data-leave-requests="own"', 'Leave Request',
            'data-leave-search', 'data-leave-filter="status"', 'value="Pending"', 'value="Approved"', 'value="Rejected"', 'value="Cancelled"',
            'data-leave-filter="type"', 'data-leave-clear',
            'data-leave-sort-key="type"', 'data-leave-sort-key="startDate"', 'data-leave-sort-key="endDate"',
            'data-leave-sort-key="filedOn"', 'data-leave-sort-key="status"', 'HR Remarks',
            'data-leave-rows', 'data-leave-empty', 'data-leave-pagination',
            'id="leave-details-modal"', 'id="leave-cancel-modal"', 'data-leave-cancel-confirm>Cancel Request',
        ], escape: false);

    expect($html)
        ->not->toContain('data-leave-sort-key="employeeName"')
        ->not->toContain('>Actions<')
        ->not->toContain('data-leave-decision-modal')
        ->not->toContain('data-leave-approve')
        ->not->toContain('data-leave-reject');
});

it('keeps the leave request list off the Employee Calendar page', function () {
    $this->get(route('employee.calendar'))
        ->assertSee('data-page="calendar"', escape: false)
        ->assertDontSee('data-leave-requests="own"', escape: false)
        ->assertDontSee('data-leave-rows', escape: false)
        ->assertDontSee('id="leave-cancel-modal"', escape: false)
        ->assertDontSee('id="leave-request-modal"', escape: false)
        ->assertDontSee('data-leave-request-form', escape: false);
});

it('adds Leave Request to the employee sidebar after Calendar, and highlights only the page that is open', function () {
    $leaveUrl = route('employee.calendar', ['view' => 'leave-requests']);

    $this->get(route('employee.dashboard'))
        ->assertSeeInOrder([
            'href="'.route('employee.calendar').'">', 'Calendar',
            'aria-current="false" href="'.$leaveUrl.'">', 'Leave Request',
        ], escape: false);

    $this->get(route('employee.calendar'))
        ->assertSee('aria-current="page" href="'.route('employee.calendar').'"', escape: false)
        ->assertSee('aria-current="false" href="'.$leaveUrl.'"', escape: false);

    $this->get($leaveUrl)
        ->assertSee('aria-current="page" href="'.$leaveUrl.'"', escape: false)
        ->assertSee('aria-current="false" href="'.route('employee.calendar').'"', escape: false);
});

it('leaves the HR calendar with its event types, all five statuses, and approve action wiring', function () {
    $this->get(route('calendar.index'))
        ->assertSee('data-calendar-type-filter', escape: false)
        ->assertSee('value="Scheduled"', escape: false)
        ->assertSee('data-employees-url', escape: false)
        ->assertDontSee('data-calendar-scope', escape: false);
});
