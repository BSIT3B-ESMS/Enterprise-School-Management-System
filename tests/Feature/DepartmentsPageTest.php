<?php

it('renders the departments toolbar, search, and sortable table headers', function () {
    $this->get(route('departments.index'))
        ->assertSeeInOrder(['Departments', 'Manage academic units, colleges, and faculty distribution', 'Add Department'])
        ->assertSee('data-department-search', escape: false)
        ->assertSeeInOrder([
            'data-sort-key="name"', 'Department Name',
            'data-sort-key="headcount"', 'No. of Faculty',
        ], escape: false)
        ->assertDontSee('Action</th>', escape: false);
});

it('opens the add department form from the toolbar', function () {
    $this->get(route('departments.index'))
        ->assertSee('data-bs-target="#department-form-modal" data-department-add', escape: false);
});

it('renders the details, add and edit, and delete modals', function () {
    $this->get(route('departments.index'))
        ->assertSee('id="department-details-modal"', escape: false)
        ->assertSee('id="department-form-modal"', escape: false)
        ->assertSee('id="department-delete-modal"', escape: false);
});

it('points the details modal and page at the employees list for faculty links', function () {
    $this->get(route('departments.index'))
        ->assertSee('data-employees-url="'.route('employees.index').'"', escape: false)
        ->assertSee('href="'.route('employees.index').'" data-department-employees-link', escape: false);
});

it('marks the departments sidebar link as the current page', function () {
    $this->get(route('departments.index'))
        ->assertSee('aria-current="page" href="'.route('departments.index').'"', escape: false)
        ->assertDontSee('aria-current="page" href="'.route('employees.index').'"', escape: false);
});

it('links departments from the sidebar instead of marking it coming soon', function () {
    $this->get(route('dashboard'))
        ->assertSee('href="'.route('departments.index').'"', escape: false)
        ->assertDontSee('data-coming-soon="Departments"', escape: false);
});

it('offers only edit and delete as actions in the details modal', function () {
    $this->get(route('departments.index'))
        ->assertSeeInOrder([
            'id="department-details-modal"',
            'data-bs-target="#department-delete-modal" data-department-delete>Delete</button>',
            'data-bs-target="#department-form-modal" data-department-edit>Edit</button>',
            'id="department-delete-modal"',
        ], escape: false)
        ->assertDontSee('data-bs-dismiss="modal">Close</button>', escape: false);
});
