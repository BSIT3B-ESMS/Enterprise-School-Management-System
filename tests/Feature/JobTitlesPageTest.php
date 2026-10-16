<?php

it('renders the job titles toolbar, search, department filter, and sortable table headers', function () {
    $this->get(route('job-titles.index'))
        ->assertSeeInOrder([
            'Job Titles',
            'Manage and organize academic ranks and faculty titles across collegiate departments.',
            'Add Job Title',
        ])
        ->assertSee('data-job-title-search', escape: false)
        ->assertSee('data-job-title-filter="departmentId"', escape: false)
        ->assertSeeInOrder([
            'data-sort-key="name"', 'Job Title',
            'data-sort-key="department"', 'Department',
            'data-sort-key="salary"', 'Base Salary',
            'data-sort-key="benefits"', 'Benefits',
        ], escape: false)
        ->assertDontSee('Action</th>', escape: false);
});

it('opens the add job title form from the toolbar', function () {
    $this->get(route('job-titles.index'))
        ->assertSee('data-bs-target="#job-title-form-modal" data-job-title-add', escape: false);
});

it('renders the job title form with name, department, salary, and benefits', function () {
    $this->get(route('job-titles.index'))
        ->assertSeeInOrder([
            'id="job-title-form-modal"',
            'name="name"', 'name="departmentId"', 'name="salary"',
            'data-job-title-benefit-options', 'Cancel', 'Save',
        ], escape: false);
});

it('offers only edit and delete as actions in the details modal', function () {
    $this->get(route('job-titles.index'))
        ->assertSeeInOrder([
            'id="job-title-details-modal"',
            'data-bs-target="#job-title-delete-modal" data-job-title-delete>Delete</button>',
            'data-bs-target="#job-title-form-modal" data-job-title-edit>Edit</button>',
            'id="job-title-delete-modal"',
        ], escape: false)
        ->assertDontSee('data-bs-dismiss="modal">Close</button>', escape: false);
});

it('points the details modal and page at the employees list for holder links', function () {
    $this->get(route('job-titles.index'))
        ->assertSee('data-employees-url="'.route('employees.index').'"', escape: false)
        ->assertSee('href="'.route('employees.index').'" data-job-title-employees-link', escape: false);
});

it('marks the job titles sidebar link as the current page', function () {
    $this->get(route('job-titles.index'))
        ->assertSee('aria-current="page" href="'.route('job-titles.index').'"', escape: false)
        ->assertDontSee('aria-current="page" href="'.route('departments.index').'"', escape: false);
});

it('links job titles from the sidebar instead of marking it coming soon', function () {
    $this->get(route('dashboard'))
        ->assertSee('href="'.route('job-titles.index').'"', escape: false)
        ->assertDontSee('data-coming-soon="Job Titles"', escape: false);
});
