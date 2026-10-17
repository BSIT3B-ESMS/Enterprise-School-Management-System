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
            'value="Filed"', 'checked data-calendar-status-filter',
            'value="Pending"', 'value="Approved"',
            'data-calendar-clear>Clear Filters</button>',
            'data-calendar-legend',
        ], escape: false)
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

it('leaves the HR calendar with its event types, all five statuses, and approve action wiring', function () {
    $this->get(route('calendar.index'))
        ->assertSee('data-calendar-type-filter', escape: false)
        ->assertSee('value="Scheduled"', escape: false)
        ->assertSee('data-employees-url', escape: false)
        ->assertDontSee('data-calendar-scope', escape: false);
});
