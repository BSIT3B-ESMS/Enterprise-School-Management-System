<?php

it('renders the recruitment toolbar, pipeline, filters, and sortable table headers', function () {
    $this->get(route('recruitment.index'))
        ->assertSeeInOrder(['Recruitment', 'Track applicants from first application to hire.', 'Add Application', 'Recruitment pipeline'])
        ->assertSeeInOrder([
            'data-application-pipeline', 'data-application-stages',
            'data-application-search', 'data-application-filter="status"', 'data-application-filter="departmentId"',
        ], escape: false)
        ->assertSeeInOrder([
            'data-sort-key="name"', 'Applicant',
            'data-sort-key="jobTitle"', 'Position Applied For',
            'data-sort-key="appliedOn"', 'Application Date',
            'data-sort-key="status"', 'Status',
        ], escape: false)
        ->assertDontSee('Action</th>', escape: false);
});

it('opens the add application form from the toolbar', function () {
    $this->get(route('recruitment.index'))
        ->assertSee('data-bs-target="#application-form-modal" data-application-add', escape: false);
});

it('renders the application form with every application field', function () {
    $this->get(route('recruitment.index'))
        ->assertSeeInOrder([
            'id="application-form-modal"',
            'Applicant Name', 'name="name"',
            'Email Address', 'name="email"',
            'Phone Number', 'name="phone"',
            'Department', 'name="departmentId"',
            'Position Applied For', 'name="jobTitleId"',
            'Application Date', 'name="appliedOn"',
            'Application Status', 'name="status"',
            'Cancel', 'Save',
        ], escape: false);
});

it('offers only edit and delete as actions in the details modal', function () {
    $this->get(route('recruitment.index'))
        ->assertSeeInOrder([
            'id="application-details-modal"',
            'data-bs-target="#application-delete-modal" data-application-delete>Delete</button>',
            'data-bs-target="#application-form-modal" data-application-edit>Edit</button>',
            'id="application-delete-modal"',
        ], escape: false)
        ->assertDontSee('data-bs-dismiss="modal">Close</button>', escape: false);
});

it('marks the recruitment sidebar link as the current page', function () {
    $this->get(route('recruitment.index'))
        ->assertSee('aria-current="page" href="'.route('recruitment.index').'"', escape: false)
        ->assertDontSee('aria-current="page" href="'.route('job-titles.index').'"', escape: false);
});

it('links recruitment from the sidebar and the pending candidates notification', function () {
    $this->get(route('dashboard'))
        ->assertSee('href="'.route('recruitment.index').'"', escape: false)
        ->assertSee('href="'.route('recruitment.index', ['status' => 'Applied']).'" data-pending-candidates', escape: false)
        ->assertDontSee('data-coming-soon="Recruitment"', escape: false);
});
