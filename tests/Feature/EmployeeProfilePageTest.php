<?php

it('renders the 201 file sections as tabs in order', function () {
    $this->get(route('employees.show', 'EMP-1001'))
        ->assertSeeInOrder([
            'Personal Information', 'Employment Information', 'Contact and Emergency', 'Government and Tax',
            'Compensation and Benefits', 'Education and History', 'Performance and Training',
            'Leave and Attendance', 'Disciplinary and Separation',
        ])
        ->assertSee('data-employee-id="EMP-1001"', escape: false);
});

it('shows the 201 file government numbers and document records in their tabs', function () {
    $this->get(route('employees.show', 'EMP-1001'))
        ->assertSeeInOrder([
            'data-profile-documents="hiringRequirements"',
            'data-profile-field="sssNumber"', 'data-profile-field="philhealthNumber"', 'data-profile-field="pagibigNumber"',
            'data-profile-field="tin"', 'data-profile-field="birNumber"',
            'data-profile-documents="birForm2316"', 'data-profile-government',
            'data-profile-documents="education"', 'data-profile-documents="workHistory"',
            'data-profile-documents="performance"',
            'data-profile-documents="correctiveActions"', 'data-profile-documents="clearances"',
            'data-profile-documents="postEmployment"',
        ], escape: false);
});

it('shows empty states for information the system does not record', function () {
    $this->get(route('employees.show', 'EMP-1001'))
        ->assertSeeInOrder([
            'No emergency contact on file',
            'Leave Requests', 'No leave requests on file',
            'No separation record',
        ]);
});

it('edits the employee on the page with the shared form, save changes, and cancel', function () {
    $this->get(route('employees.show', 'EMP-1001'))
        ->assertSee('data-profile-edit-start', escape: false)
        ->assertSeeInOrder([
            'data-profile-edit-form',
            'Personal Information', 'name="name"', 'name="birthdate"',
            'Employment Information', 'name="departmentId"', 'name="jobTitleId"', 'name="hiredOn"',
            'Contact Information', 'name="email"',
            'Government and Tax', 'name="sssNumber"', 'name="tin"',
            'Compensation and Benefits', 'name="salary"',
            'Education and Work History', 'data-document-field="education"',
            'data-profile-edit-cancel', 'Cancel', 'Save Changes',
        ], escape: false)
        ->assertDontSee('id="employee-form-modal"', escape: false)
        ->assertDontSee('id="employee-details-modal"', escape: false);
});

it('keeps delete on the profile with its confirmation', function () {
    $this->get(route('employees.show', 'EMP-1001'))
        ->assertSee('data-bs-target="#employee-delete-modal" data-employee-delete="EMP-1001"', escape: false)
        ->assertSee('id="employee-delete-modal"', escape: false);
});

it('keeps the employees sidebar link current and has a labelled back to employees link', function () {
    $this->get(route('employees.show', 'EMP-1001'))
        ->assertSee('aria-current="page" href="'.route('employees.index').'"', escape: false)
        ->assertSee('data-employee-back-link data-employees-url="'.route('employees.index').'"', escape: false)
        ->assertSee('Back to Employees');
});

it('does not treat the new employee page as a profile', function () {
    $this->get(route('employees.create'))
        ->assertSee('New Employee')
        ->assertDontSee('data-page="employee-profile"', escape: false);
});

it('returns 404 for a profile address that is not an employee ID', function () {
    $this->get('/employees/not-an-id')->assertNotFound();
});
