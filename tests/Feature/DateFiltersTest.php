<?php

it('renders inclusive start and end date filters with a clear button on the :route page', function (string $route, string $name) {
    $this->get(route($route))
        ->assertSeeInOrder([
            'for="'.$name.'-start-date"', 'Start Date',
            'type="date"', 'data-'.$name.'-filter="startDate"',
            'for="'.$name.'-end-date"', 'End Date',
            'data-'.$name.'-filter="endDate"',
            'data-'.$name.'-clear>Clear Filters</button>',
            'data-date-range-error',
        ], escape: false);
})->with([
    'employees' => ['employees.index', 'employee'],
    'recruitment' => ['recruitment.index', 'application'],
    'audit logs' => ['audit.index', 'audit'],
]);

it('describes the date being filtered on each page', function (string $route, string $subject) {
    $this->get(route($route))
        ->assertSee('Show records with a '.$subject.' on or after this date', escape: false)
        ->assertSee('Show records with a '.$subject.' on or before this date', escape: false);
})->with([
    'employees' => ['employees.index', 'Date Hired'],
    'recruitment' => ['recruitment.index', 'Application Date'],
    'audit logs' => ['audit.index', 'Activity Date and Time'],
]);
