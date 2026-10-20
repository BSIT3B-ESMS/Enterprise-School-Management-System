<?php

beforeEach(fn () => $this->actingAs(employeeUser()));

it('serves My Files with the sidebar My Files item current', function () {
    $this->get(route('employee.files'))
        ->assertOk()
        ->assertSee('data-page="portal-files"', escape: false)
        ->assertSee('aria-current="page" href="'.route('employee.files').'"', escape: false);
});

it('has a summary, the empty states, and the category list mount', function () {
    $this->get(route('employee.files'))
        ->assertSeeInOrder([
            'My Files', 'data-files-summary',
            'data-portal-missing', 'Employee record not found',
            'data-files-none', 'No files on record',
            'data-files-categories',
        ], escape: false);
});

it('offers no preview or download controls, because document records hold only a filename', function () {
    $this->get(route('employee.files'))
        ->assertDontSee('download', escape: false)
        ->assertDontSee('Download', escape: false)
        ->assertDontSee('Preview', escape: false)
        ->assertDontSee('data-document-input', escape: false)
        ->assertDontSee('type="file"', escape: false);
});

it('keeps HR pages and other employees out of My Files', function () {
    $this->get(route('employee.files'))
        ->assertDontSee('href="'.route('employees.index').'"', escape: false)
        ->assertDontSee('data-employee-', escape: false);
});
