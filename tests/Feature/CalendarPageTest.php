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
            'id="calendar-type-screening" value="screening"', 'checked data-calendar-type-filter', 'Applicant Screening',
            'id="calendar-type-interview" value="interview"', 'checked data-calendar-type-filter', 'Applicant Interview',
            'Event Status',
            'value="Filed"', 'value="Pending"', 'value="Approved"', 'value="Scheduled"', 'value="Completed"',
            'data-calendar-clear>Clear Filters</button>',
            'data-calendar-legend',
        ], escape: false);
});

it('renders month navigation with a today button, the grid, the day panel, and event details', function () {
    $this->get(route('calendar.index'))
        ->assertSeeInOrder([
            'data-calendar-month-title',
            'aria-label="Previous month"', 'data-calendar-today>Today</button>', 'aria-label="Next month"',
            'data-calendar-month-empty',
            'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat',
            'data-calendar-grid',
            'data-calendar-day-title', 'data-calendar-day-events', 'data-calendar-day-empty',
            'Event Details', 'data-calendar-details', 'Select an event to view its details.',
        ], escape: false);
});

it('links event details to employee profiles and recruitment', function () {
    $this->get(route('calendar.index'))
        ->assertSee('data-employees-url="'.url('/employees').'"', escape: false)
        ->assertSee('data-recruitment-url="'.route('recruitment.index').'"', escape: false);
});
