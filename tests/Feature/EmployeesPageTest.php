<?php

it('renders the employees toolbar, filters, and sortable table headers', function () {
    $this->get(route('employees.index'))
        ->assertOk()
        ->assertSeeInOrder(['Employees', 'Copy CSV', 'Add Employee'])
        ->assertSee('data-employee-search', escape: false)
        ->assertSee('data-employee-filter="jobTitleId"', escape: false)
        ->assertSee('data-employee-filter="departmentId"', escape: false)
        ->assertSeeInOrder([
            'data-sort-key="id"', 'data-sort-key="name"', 'data-sort-key="department"',
            'data-sort-key="jobTitle"', 'data-sort-key="hiredOn"',
        ], escape: false)
        ->assertDontSee('Action</th>', escape: false);
});

it('links Add Employee to the new employee page', function () {
    $this->get(route('employees.index'))
        ->assertSee('href="'.route('employees.create').'"', escape: false);
});

it('renders the new employee page with the full form and a way back to the list', function () {
    $this->get(route('employees.create'))
        ->assertOk()
        ->assertSee('New Employee')
        ->assertSee('aria-label="Back to employees"', escape: false)
        ->assertSee('data-employee-form-redirect="'.route('employees.index').'"', escape: false)
        ->assertSeeInOrder([
            'Personal and Contact', 'name="name"', 'name="email"',
            'Employment', 'name="departmentId"', 'name="jobTitleId"', 'name="hiredOn"', 'name="salary"',
            'Benefits', 'Cancel', 'Create Employee',
        ], escape: false)
        ->assertDontSee('id="employee-form-modal"', escape: false);
});

it('keeps the employees sidebar link current on the new employee page', function () {
    $this->get(route('employees.create'))
        ->assertSee('aria-current="page" href="'.route('employees.index').'"', escape: false);
});

it('renders the view, edit, and delete modals', function () {
    $this->get(route('employees.index'))
        ->assertSee('id="employee-form-modal"', escape: false)
        ->assertSee('id="employee-details-modal"', escape: false)
        ->assertSee('id="employee-delete-modal"', escape: false);
});

it('marks the employees sidebar link as the current page', function () {
    $this->get(route('employees.index'))
        ->assertSee('aria-current="page" href="'.route('employees.index').'"', escape: false)
        ->assertDontSee('aria-current="page" href="'.route('dashboard').'"', escape: false);
});
