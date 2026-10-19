<?php

it('serves the applicant calendar with Calendar current in the applicant sidebar', function () {
    $this->get(route('applicant.calendar'))
        ->assertOk()
        ->assertSee('data-page="calendar" data-calendar-scope="applicant"', escape: false)
        ->assertSee('aria-current="page" href="'.route('applicant.calendar').'"', escape: false);
});

it('offers demo interview and interview types and scheduled and completed statuses, all selected', function () {
    $this->get(route('applicant.calendar'))
        ->assertSeeInOrder([
            'Event Type',
            'id="calendar-type-demo-interview" value="demo-interview"', 'checked data-calendar-type-filter', 'Demo Interview',
            'id="calendar-type-interview" value="interview"', 'Interview',
            'Event Status',
            'value="Scheduled"', 'checked data-calendar-status-filter', 'value="Completed"',
            'data-calendar-clear>Clear Filters</button>',
            'data-calendar-legend',
        ], escape: false)
        ->assertDontSee('value="leave"', escape: false)
        ->assertDontSee('value="Filed"', escape: false)
        ->assertDontSee('value="Pending"', escape: false)
        ->assertDontSee('value="Approved"', escape: false);
});

it('reuses the calendar navigation, grid, day panel, and details', function () {
    $this->get(route('applicant.calendar'))
        ->assertSeeInOrder([
            'data-calendar-data-notice',
            'data-calendar-month-title',
            'aria-label="Previous month"', 'data-calendar-today>Today</button>', 'aria-label="Next month"',
            'data-calendar-month-empty',
            'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat',
            'data-calendar-grid',
            'data-calendar-day-title', 'data-calendar-day-events', 'data-calendar-day-empty',
            'Event Details', 'data-calendar-details', 'Select an event to view its details.',
        ], escape: false);
});

it('has no HR actions, other applicants, or HR links on the applicant calendar', function () {
    $this->get(route('applicant.calendar'))
        ->assertDontSee('data-calendar-approve', escape: false)
        ->assertDontSee('data-employees-url', escape: false)
        ->assertDontSee('data-recruitment-url', escape: false)
        ->assertDontSee('href="'.route('recruitment.index').'"', escape: false)
        ->assertDontSee('href="'.route('employees.index').'"', escape: false);
});

it('leaves the HR calendar unchanged', function () {
    $this->get(route('calendar.index'))
        ->assertSee('value="leave"', escape: false)
        ->assertSee('value="Filed"', escape: false)
        ->assertSee('data-employees-url', escape: false)
        ->assertDontSee('data-calendar-scope', escape: false);
});

it('keeps the side panels on the applicant calendar and adds Other schedules to its filter', function () {
    $this->get(route('applicant.calendar'))
        ->assertSee('id="calendar-type-other" value="other"', escape: false)
        ->assertSee('data-calendar-day-title', escape: false)
        ->assertSee('data-calendar-details', escape: false)
        ->assertDontSee('data-calendar-interactive', escape: false)
        ->assertDontSee('data-unscheduled-panel', escape: false)
        ->assertDontSee('calendar-schedule-modal', escape: false);
});
