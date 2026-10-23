<?php

it('serves the directory with the sidebar Directory item current', function () {
    $this->get(route('employee.directory'))
        ->assertOk()
        ->assertSee('data-page="portal-directory"', escape: false)
        ->assertSee('aria-current="page" href="'.route('employee.directory').'"', escape: false);
});

it('has search, the list, and the empty states, but no college filter', function () {
    $this->get(route('employee.directory'))
        ->assertSeeInOrder([
            'Directory', 'data-directory-subtitle',
            'data-portal-missing', 'Employee record not found',
            'data-directory-no-department', 'No department assigned',
            'data-directory-search', 'data-directory-clear>Clear Search</button>',
            'data-directory-list', 'data-directory-empty>', 'No colleagues match',
            'data-directory-summary', 'data-directory-pagination',
        ], escape: false)
        ->assertDontSee('data-directory-department', escape: false)
        ->assertDontSee('All Colleges')
        ->assertDontSee('<select', escape: false);
});

it('does not expose addresses, government IDs, pay, or 201 file records on the directory page', function () {
    $this->get(route('employee.directory'))
        ->assertDontSee('address', escape: false)
        ->assertDontSee('Address', escape: false)
        ->assertDontSee('sssNumber', escape: false)
        ->assertDontSee('SSS', escape: false)
        ->assertDontSee('TIN', escape: false)
        ->assertDontSee('salary', escape: false)
        ->assertDontSee('Salary', escape: false)
        ->assertDontSee('data-document', escape: false)
        ->assertDontSee('href="'.route('employees.index').'"', escape: false);
});
