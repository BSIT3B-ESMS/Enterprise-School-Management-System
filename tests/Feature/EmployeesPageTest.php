<?php

it('renders the employees toolbar, filters, and sortable table headers', function () {
    $this->get(route('employees.index'))
        ->assertOk()
        ->assertSeeInOrder(['Employees', 'Generate Report', 'Add Employee'])
        ->assertDontSee('Copy CSV')
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
            'Personal Information', 'name="name"', 'name="birthdate"', 'name="sex"', 'name="civilStatus"',
            'Employment Information', 'name="departmentId"', 'name="jobTitleId"', 'name="hiredOn"',
            'Contact Information', 'name="email"', 'name="phone"', 'name="address"',
            'Government and Tax', 'Cancel', 'Create Employee',
        ], escape: false)
        ->assertDontSee('id="employee-form-modal"', escape: false);
});

it('includes the 201 file government numbers, compensation, and document records on the new employee form', function () {
    $this->get(route('employees.create'))
        ->assertSeeInOrder([
            'data-document-field="hiringRequirements"',
            'Government and Tax',
            'name="sssNumber"', 'name="philhealthNumber"', 'name="pagibigNumber"', 'name="tin"', 'name="birNumber"',
            'data-document-field="birForm2316"',
            'Compensation and Benefits', 'name="salary"', 'data-benefit-options',
            'Education and Work History', 'data-document-field="education"', 'data-document-field="workHistory"',
            'Performance', 'data-document-field="performance"',
            'Disciplinary and Separation', 'data-document-field="correctiveActions"',
            'data-document-field="clearances"', 'data-document-field="postEmployment"',
        ], escape: false);
});

it('keeps the employees sidebar link current on the new employee page', function () {
    $this->get(route('employees.create'))
        ->assertSee('aria-current="page" href="'.route('employees.index').'"', escape: false);
});

it('opens employees on their own page instead of in modals', function () {
    $this->get(route('employees.index'))
        ->assertSee('data-profile-base="'.url('/employees').'"', escape: false)
        ->assertDontSee('id="employee-details-modal"', escape: false)
        ->assertDontSee('id="employee-form-modal"', escape: false)
        ->assertDontSee('id="employee-delete-modal"', escape: false);
});

it('marks the employees sidebar link as the current page', function () {
    $this->get(route('employees.index'))
        ->assertSee('aria-current="page" href="'.route('employees.index').'"', escape: false)
        ->assertDontSee('aria-current="page" href="'.route('dashboard').'"', escape: false);
});

it('filters by hiring date with a month, a From Date and a To Date, applied with Apply Filter', function () {
    $this->get(route('employees.index'))
        ->assertSeeInOrder([
            'data-hired-filter', 'Date Hired',
            '<label for="employee-hired-month" class="form-label">Month</label>', 'type="month"', 'data-hired-month',
            '<label for="employee-hired-from" class="form-label">From Date</label>', 'type="date"', 'data-hired-from',
            '<label for="employee-hired-to" class="form-label">To Date</label>', 'data-hired-to',
            'data-hired-apply>Apply Filter</button>', 'data-hired-clear>Clear Filter</button>',
            'data-employee-clear>Clear Filters</button>',
        ], escape: false)
        ->assertDontSee('data-employee-filter="hiredOn"', escape: false);
});

it('has an Archived Employees button in the lower right of the Employees page', function () {
    $this->get(route('employees.index'))
        ->assertSeeInOrder([
            'data-employee-pagination',
            'justify-content-end', 'href="'.route('employees.archived').'"', 'Archived Employees', 'data-archived-count',
        ], escape: false);
});

it('serves the Archived Employees page with Restore and Delete, and the only delete confirmation', function () {
    $this->get(route('employees.archived'))
        ->assertOk()
        ->assertSee('data-page="archived-employees"', escape: false)
        ->assertSee('aria-current="page" href="'.route('employees.index').'"', escape: false)
        ->assertSeeInOrder([
            'Archived Employees', 'data-archived-search',
            'Employee ID', 'Name', 'Department', 'Job Title', 'Date Hired', 'Archived On', 'Actions',
            'data-archived-rows', 'data-archived-empty', 'No archived employees',
            'id="employee-delete-modal"', 'Delete archived employee', 'data-employee-delete-confirm',
        ], escape: false);
});

it('shows a sortable Base Salary column between Job Title and Date Hired', function () {
    $this->get(route('employees.index'))
        ->assertSeeInOrder([
            'data-sort-key="jobTitle"', 'data-sort-key="salary"', 'Base Salary', 'data-sort-key="hiredOn"',
        ], escape: false);
});
