<?php

beforeEach(fn () => $this->actingAs(hrHead()));

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

it('renders the application form with every field except a status', function () {
    $this->get(route('recruitment.index'))
        ->assertSeeInOrder([
            'id="application-form-modal"',
            'Applicant Name', 'name="name"',
            'Email Address', 'name="email"',
            'Phone Number', 'name="phone"',
            'Department', 'name="departmentId"',
            'Position Applied For', 'name="jobTitleId"',
            'Application Date', 'name="appliedOn"',
            'Cancel', 'Save',
        ], escape: false)
        ->assertDontSee('name="status"', escape: false)
        ->assertDontSee('data-application-status-options', escape: false);
});

it('shows the recruitment workflow sections in the details modal in order', function () {
    $this->get(route('recruitment.index'))
        ->assertSeeInOrder([
            'id="application-details-modal"',
            'Application Status', 'Employee Record',
            'Recruitment Progress', 'data-application-steps',
            'Next Step', 'Status changes only when a milestone is reached',
            'data-application-next-actions', 'data-application-activity-form',
            'name="date"', 'name="time"', 'data-application-confirm',
            'Demo Interview and Interviews', 'data-application-activities',
            'Completed Milestones', 'data-application-milestones',
            'data-application-history-section',
        ], escape: false);
});

it('does not let the status be picked from the table', function () {
    $this->get(route('recruitment.index'))
        ->assertDontSee('data-application-status=', escape: false)
        ->assertDontSee('application-status-select', escape: false);
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

it('filters by a single application date picker with no start or end date fields', function () {
    $this->get(route('recruitment.index'))
        ->assertSeeInOrder([
            '<label class="input-group-text" for="application-applied-on-filter">Application Date</label>',
            'type="date"', 'data-application-filter="appliedOn"',
            'data-application-clear>Clear Filters</button>',
        ], escape: false)
        ->assertDontSee('Start Date')
        ->assertDontSee('End Date');
});

it('has the applicant list the Calendar links into, with rows the highlight can find', function () {
    $this->get(route('recruitment.index'))
        ->assertSee('data-page="recruitment"', escape: false)
        ->assertSeeInOrder(['data-application-rows', 'data-application-empty', 'data-application-pagination'], escape: false);
});
