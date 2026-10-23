<?php

it('serves the applicant profile, linked from the header menu and the sidebar', function () {
    $this->get(route('applicant.profile'))
        ->assertOk()
        ->assertSee('data-page="applicant-profile"', escape: false)
        ->assertSee('aria-current="page" href="'.route('applicant.profile').'"', escape: false);

    $this->get(route('applicant.dashboard'))
        ->assertSeeInOrder(['href="'.route('applicant.profile').'"', 'My Profile'], escape: false);
});

it('shows the applicant\'s 201 file details in tabs, with the name in parts', function () {
    $this->get(route('applicant.profile'))
        ->assertSeeInOrder([
            'My Profile',
            'data-applicant-edit-start>Edit',
            'Personal Information', 'Contact Information', 'Documents',
            'data-applicant-field="lastName"', 'data-applicant-field="firstName"', 'data-applicant-field="middleName"',
            'data-applicant-field="birthdate"', 'data-applicant-field="createdOn"',
            'data-applicant-field="email"', 'data-applicant-field="phone"', 'data-applicant-field="address"',
            'data-applicant-documents="hiringRequirements"',
        ], escape: false);
});

it('leaves government and tax numbers out of the profile: they are pre-employment information', function () {
    $this->get(route('applicant.profile'))
        ->assertDontSee('Government and Tax')
        ->assertDontSee('sssNumber', escape: false)
        ->assertDontSee('birNumber', escape: false);
});

it('lets the applicant edit their details with Save Changes and Cancel; the name is read-only and the application is not in the form', function () {
    $html = $this->get(route('applicant.profile'))->getContent();
    $form = substr($html, strpos($html, 'data-applicant-form'));

    expect($form)
        ->toContain('readonly data-applicant-input="lastName"', 'readonly data-applicant-input="firstName"', 'readonly data-applicant-input="middleName"')
        ->toContain('name="birthdate"', 'name="sex"', 'name="civilStatus"', 'name="email"', 'name="phone"', 'name="address"')
        ->toContain('for="applicant-birthdate" class="form-label is-required"', 'for="applicant-sex" class="form-label is-required"', 'for="applicant-civil-status" class="form-label is-required"')
        ->toContain('data-applicant-edit-cancel>Cancel', 'Save Changes')
        ->and($form)->not->toContain('name="name"', 'name="jobTitleId"', 'name="departmentId"', 'name="appliedOn"', 'name="status"', 'type="file"');
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
