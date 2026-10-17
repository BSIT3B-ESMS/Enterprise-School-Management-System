<?php

it('serves the applicant profile, linked from the header menu and the sidebar', function () {
    $this->get(route('applicant.profile'))
        ->assertOk()
        ->assertSee('data-page="applicant-profile"', escape: false)
        ->assertSee('aria-current="page" href="'.route('applicant.profile').'"', escape: false);

    $this->get(route('applicant.dashboard'))
        ->assertSeeInOrder(['href="'.route('applicant.profile').'"', 'My Profile'], escape: false);
});

it('shows the personal and application information in tabs, with a way back to the dashboard', function () {
    $this->get(route('applicant.profile'))
        ->assertSeeInOrder([
            'My Profile', 'Back to Dashboard',
            'data-applicant-missing', 'No application found',
            'data-applicant-edit-start>Edit',
            'Personal Information', 'Application',
            'data-applicant-field="name"', 'data-applicant-field="email"', 'data-applicant-field="phone"',
            'data-applicant-field="jobTitle"', 'data-applicant-field="department"',
            'data-applicant-field="appliedOn"', 'data-applicant-field="status"',
        ], escape: false)
        ->assertSee('href="'.route('applicant.dashboard').'"', escape: false);
});

it('lets the applicant edit only their email and phone; the name is read-only and the application is not in the form', function () {
    $html = $this->get(route('applicant.profile'))->getContent();
    $form = substr($html, strpos($html, 'data-applicant-form'));

    expect($form)
        ->toContain('name="email"', 'name="phone"', 'readonly data-applicant-input="name"', 'data-applicant-edit-cancel', 'Save Changes')
        ->and($form)->not->toContain('name="name"', 'name="jobTitleId"', 'name="departmentId"', 'name="appliedOn"', 'name="status"');
});

it('has no Employee or HR profile fields on the applicant profile', function () {
    $this->get(route('applicant.profile'))
        ->assertDontSee('salary', escape: false)
        ->assertDontSee('Employment Status', escape: false)
        ->assertDontSee('Date Hired', escape: false)
        ->assertDontSee('data-profile-', escape: false)
        ->assertDontSee('data-portal-', escape: false)
        ->assertDontSee('data-hr-', escape: false);
});
