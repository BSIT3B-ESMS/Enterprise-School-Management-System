<?php

it('adds calendar to the main navigation and marks it current on its page', function () {
    $this->get(route('dashboard'))
        ->assertSee('href="'.route('calendar.index').'"', escape: false);

    $this->get(route('calendar.index'))
        ->assertSee('aria-current="page" href="'.route('calendar.index').'"', escape: false);
});

it('renders event type and status filters, all selected, with clear filters', function () {
    $this->get(route('calendar.index'))
        ->assertSeeInOrder([
            'Event Type',
            'id="calendar-type-leave" value="leave"', 'checked data-calendar-type-filter', 'Employee Leave',
            'id="calendar-type-demo-interview" value="demo-interview"', 'checked data-calendar-type-filter', 'Applicant Demo Interview',
            'id="calendar-type-interview" value="interview"', 'checked data-calendar-type-filter', 'Applicant Final Interview',
            'id="calendar-type-other" value="other"', 'checked data-calendar-type-filter', 'Other Applicant Event',
            'Event Status',
            'value="Pending"', 'value="Approved"', 'value="Scheduled"', 'value="Completed"',
            'data-calendar-clear>Clear Filters</button>',
            'data-calendar-legend',
        ], escape: false);
});

it('renders month navigation with a today button, the grid, and Unscheduled Applicants beside it at the top', function () {
    $this->get(route('calendar.index'))
        ->assertSee('data-page="calendar" data-calendar-interactive', escape: false)
        ->assertSeeInOrder([
            'data-calendar-month-title',
            'aria-label="Previous month"', 'data-calendar-today>Today</button>', 'aria-label="Next month"',
            'data-calendar-month-empty',
            'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat',
            'data-calendar-grid',
            'data-unscheduled-panel', 'Unscheduled Applicants', 'data-unscheduled-count', 'data-unscheduled-list', 'data-unscheduled-empty',
        ], escape: false)
        ->assertDontSee('data-calendar-day-title', escape: false)
        ->assertDontSee('data-calendar-details', escape: false);
});

it('opens a date and an event in modals, with the event history and the Reschedule and Cancel actions', function () {
    $this->get(route('calendar.index'))
        ->assertSeeInOrder([
            'id="calendar-day-modal"', 'data-calendar-day-modal-events', 'data-calendar-day-schedule>Schedule on This Date',
            'id="calendar-event-modal"', 'data-calendar-event-modal-body', 'data-calendar-event-modal-actions',
        ], escape: false);
});

it('has a scheduling form with the required on-site details and no meeting link', function () {
    $html = $this->get(route('calendar.index'))->getContent();
    $form = substr($html, strpos($html, 'data-calendar-schedule-form'), strpos($html, 'data-calendar-reschedule-modal') - strpos($html, 'data-calendar-schedule-form'));

    expect($form)
        ->toContain('Applicant Name', 'readonly', 'data-schedule-applicant-select')
        ->toContain('Interview Type', 'name="type"')
        ->toContain('Schedule Date', 'type="date"', 'name="date"')
        ->toContain('Start Time', 'type="time"', 'name="time"', 'End Time', 'name="endTime"')
        ->toContain('Location / Venue', 'name="venue"', 'All interviews are held on-site.')
        ->toContain('Notes', 'name="notes"', 'Save Schedule')
        ->not->toContain('Meeting Link')->not->toContain('Online')->not->toContain('name="link"')
        ->and(substr_count($form, 'form-label is-required'))->toBe(6);
});

it('has a rescheduling form with a reason, and one confirmation with Confirm, Go Back, and Cancel', function () {
    $this->get(route('calendar.index'))
        ->assertSeeInOrder([
            'data-calendar-reschedule-form', 'Current Schedule',
            'New Date', 'name="date"', 'Start Time', 'name="time"', 'End Time', 'name="endTime"',
            'Reason for Rescheduling', 'name="reason"', 'data-reschedule-detail-field', 'Reason Details', 'name="reasonDetail"',
            'Save Changes',
            'id="calendar-confirm-modal"', 'data-confirm-summary', 'data-confirm-cancel>Cancel', 'data-confirm-back>Go Back', 'data-confirm-ok',
        ], escape: false);
});

it('links event details to employee profiles and recruitment', function () {
    $this->get(route('calendar.index'))
        ->assertSee('data-employees-url="'.url('/employees').'"', escape: false)
        ->assertSee('data-recruitment-url="'.route('recruitment.index').'"', escape: false);
});

it('has an Add Event button beside the month navigation', function () {
    $this->get(route('calendar.index'))
        ->assertSeeInOrder([
            'data-calendar-month-title',
            'data-calendar-add-event', 'Add Event',
            'aria-label="Previous month"', 'data-calendar-today>Today</button>', 'aria-label="Next month"',
        ], escape: false);
});

it('has an event form with the required fields marked, the venue, notes, and a status', function () {
    $html = $this->get(route('calendar.index'))->getContent();
    $form = substr($html, strpos($html, 'data-calendar-event-form-modal'), strpos($html, 'data-calendar-schedule-modal') - strpos($html, 'data-calendar-event-form-modal'));

    expect($form)
        ->toContain('Event Title', 'name="title"')
        ->toContain('Event Type', 'name="type"')
        ->toContain('Status', 'name="status"')
        ->toContain('Date', 'type="date"', 'name="date"')
        ->toContain('Start Time', 'type="time"', 'name="time"', 'End Time', 'name="endTime"')
        ->toContain('Location / Venue', 'name="venue"')
        ->toContain('Notes', 'name="notes"', 'data-eventform-error="title"', 'data-eventform-error="venue"')
        ->toContain('type="submit" class="btn btn-primary">Save</button>')
        ->and(substr_count($form, 'form-label is-required'))->toBe(7);
});

it('lets a date with events offer Add Event in its modal', function () {
    $this->get(route('calendar.index'))
        ->assertSeeInOrder(['id="calendar-day-modal"', 'data-calendar-day-add>Add Event', 'data-calendar-day-schedule>Schedule on This Date'], escape: false);
});

it('leaves rejected and cancelled leave, and the retired Filed status, out of the calendar filters', function () {
    $html = $this->get(route('calendar.index'))->getContent();
    $filters = substr($html, strpos($html, 'Event Status'), strpos($html, 'data-calendar-clear') - strpos($html, 'Event Status'));

    expect($filters)->not->toContain('value="Filed"')->not->toContain('value="Rejected"')->not->toContain('value="Cancelled"');
});

it('has a separate Leave Requests page where HR reviews every request, with no calendar on it', function () {
    $this->get(route('calendar.index', ['view' => 'leave-requests']))
        ->assertOk()
        ->assertDontSee('data-page="calendar"', escape: false)
        ->assertDontSee('data-calendar-grid', escape: false)
        ->assertSeeInOrder([
            'id="leave-requests"', 'data-leave-requests="hr"', 'data-employees-url="'.url('/employees').'"', 'Leave Requests',
            'data-leave-search', 'data-leave-filter="status"', 'value="Pending"', 'value="Approved"', 'value="Rejected"', 'value="Cancelled"',
            'data-leave-filter="type"', 'data-leave-clear',
            'data-leave-sort-key="employeeName"', 'Employee', 'data-leave-sort-key="type"', 'Leave Type',
            'data-leave-sort-key="startDate"', 'Start Date', 'data-leave-sort-key="endDate"', 'End Date',
            'data-leave-sort-key="filedOn"', 'Date Filed', 'data-leave-sort-key="status"', 'Status',
            'data-leave-rows', 'data-leave-empty', 'data-leave-pagination',
        ], escape: false)
        ->assertDontSee('Actions', escape: false)
        ->assertDontSee('data-leave-cancel-modal', escape: false);
});

it('asks HR for a rejection reason and a confirmation in the leave decision modal', function () {
    $this->get(route('calendar.index', ['view' => 'leave-requests']))
        ->assertSeeInOrder([
            'id="leave-details-modal"', 'data-leave-details-body', 'data-leave-details-actions',
            'id="leave-decision-modal"', 'role="alertdialog"', 'data-leave-decision-form',
            'name="remarks"', 'maxlength="500"', 'id="leave-decision-remarks-error"',
            'data-leave-decision-step="confirm"', 'Reject this request?',
            'data-leave-decision-back>Go Back', 'data-leave-decision-submit',
        ], escape: false);
});

it('keeps leave request management off the HR Calendar page', function () {
    $this->get(route('calendar.index'))
        ->assertSee('data-page="calendar"', escape: false)
        ->assertDontSee('data-leave-requests="hr"', escape: false)
        ->assertDontSee('id="leave-decision-modal"', escape: false)
        ->assertDontSee('data-leave-rows', escape: false);
});

it('adds Leave Requests to the HR sidebar after Calendar, and highlights only the page that is open', function () {
    $leaveUrl = route('calendar.index', ['view' => 'leave-requests']);

    $this->get(route('dashboard'))
        ->assertSeeInOrder([
            'href="'.route('calendar.index').'">', 'Calendar',
            'aria-current="false" href="'.$leaveUrl.'">', 'Leave Requests',
            'href="'.route('audit.index').'">', 'Audit Logs',
        ], escape: false);

    $this->get(route('calendar.index'))
        ->assertSee('aria-current="page" href="'.route('calendar.index').'">', escape: false)
        ->assertSee('aria-current="false" href="'.$leaveUrl.'">', escape: false);

    $this->get($leaveUrl)
        ->assertSee('aria-current="page" href="'.$leaveUrl.'">', escape: false)
        ->assertSee('aria-current="false" href="'.route('calendar.index').'">', escape: false);
});

it('keeps Add Event off the Employee and Applicant calendars', function (string $route) {
    $this->get(route($route))
        ->assertDontSee('data-calendar-add-event', escape: false)
        ->assertDontSee('data-calendar-event-form-modal', escape: false);
})->with(['employee.calendar', 'applicant.calendar']);

it('gives the Calendar the Recruitment address its View Applicant in Recruitment action opens', function () {
    $this->get(route('calendar.index'))
        ->assertSee('data-recruitment-url="'.route('recruitment.index').'"', escape: false)
        ->assertSee('id="calendar-event-modal"', escape: false);
});
