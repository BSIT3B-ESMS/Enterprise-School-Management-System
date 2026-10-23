<?php

it('renders the colleges toolbar, search, and sortable table headers', function () {
    $this->get(route('departments.index'))
        ->assertSeeInOrder(['Colleges', 'The university’s colleges and their faculty distribution', 'Add College'])
        ->assertSee('data-department-search', escape: false)
        ->assertSeeInOrder([
            'data-sort-key="name"', 'College Name',
            'data-sort-key="headcount"', 'No. of Faculty',
        ], escape: false)
        ->assertDontSee('Action</th>', escape: false);
});

it('opens the add college form from the toolbar', function () {
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

it('marks the colleges sidebar link as the current page', function () {
    $this->get(route('departments.index'))
        ->assertSee('aria-current="page" href="'.route('departments.index').'"', escape: false)
        ->assertDontSee('aria-current="page" href="'.route('employees.index').'"', escape: false);
});

it('links colleges from the sidebar instead of marking it coming soon', function () {
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

it('names the page Colleges in the sidebar', function () {
    $this->get(route('dashboard'))
        ->assertSee('Colleges')
        ->assertDontSee('>Departments<', escape: false);
});

it('chooses a college from the approved list instead of typing a name', function () {
    $this->get(route('departments.index'))
        ->assertSee('<label for="department-name" class="form-label is-required">College</label>', escape: false)
        ->assertSee('<select class="form-select" id="department-name" name="name"', escape: false)
        ->assertDontSee('placeholder="e.g. Department of Computer Science"', escape: false);
});
