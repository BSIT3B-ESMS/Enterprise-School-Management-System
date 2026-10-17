<?php

it('organizes the job application form into the 201 file sections', function () {
    $this->get(route('applicant.apply'))
        ->assertSeeInOrder([
            'data-apply-form',
            'Personal Information', 'name="name"', 'name="birthdate"', 'name="sex"', 'name="civilStatus"',
            'Contact Information', 'name="email"', 'name="phone"', 'name="address"',
            'Government and Tax', 'name="sssNumber"', 'name="philhealthNumber"', 'name="pagibigNumber"', 'name="tin"', 'name="birNumber"',
            'Documents', 'data-document-field="education"', 'data-document-field="workHistory"', 'data-document-field="hiringRequirements"',
            'Position', 'name="jobTitleId"',
            'Submit Application',
        ], escape: false);
});

it('uses the employee form\'s labels, dropdown options, and input types', function () {
    $this->get(route('applicant.apply'))
        ->assertSeeInOrder([
            'for="apply-sex"', '<option>Female</option>', '<option>Male</option>', '<option>Prefer not to say</option>',
            'for="apply-civil-status"', '<option>Single</option>', '<option>Married</option>', '<option>Widowed</option>', '<option>Separated</option>',
        ], escape: false)
        ->assertSee('type="date" class="form-control" id="apply-birthdate"', escape: false)
        ->assertSeeInOrder(['SSS Number', 'PhilHealth Number', 'Pag-IBIG (HDMF) Number', 'Tax Identification Number (TIN)', 'BIR Number'])
        ->assertSeeInOrder(['Education Transcripts or Diplomas', 'Work History', 'Hiring Requirements']);
});

it('uses the existing file field for documents, without asking for employment-stage records', function () {
    $this->get(route('applicant.apply'))
        ->assertSee('type="file" class="form-control" id="employee-documents-education" multiple', escape: false)
        ->assertDontSee('data-document-field="performance"', escape: false)
        ->assertDontSee('data-document-field="correctiveActions"', escape: false)
        ->assertDontSee('data-document-field="clearances"', escape: false)
        ->assertDontSee('data-document-field="postEmployment"', escape: false)
        ->assertDontSee('data-document-field="birForm2316"', escape: false)
        ->assertDontSee('name="salary"', escape: false)
        ->assertDontSee('name="benefits"', escape: false);
});

it('does not claim anything has been permanently saved', function () {
    $this->get(route('applicant.apply'))
        ->assertDontSee('permanently', escape: false)
        ->assertDontSee('has been saved', escape: false)
        ->assertDontSee('securely stored', escape: false);
});

it('shows the submitted information on the HR applicant record', function () {
    $this->get(route('recruitment.index'))
        ->assertSeeInOrder([
            'id="application-details-modal"',
            'Personal Information', 'data-application-field="birthdate"', 'data-application-field="sex"',
            'data-application-field="civilStatus"', 'data-application-field="address"',
            'Government and Tax', 'data-application-field="sssNumber"', 'data-application-field="philhealthNumber"',
            'data-application-field="pagibigNumber"', 'data-application-field="tin"', 'data-application-field="birNumber"',
            'Documents', 'data-application-documents="education"', 'data-application-documents="workHistory"',
            'data-application-documents="hiringRequirements"',
            'Recruitment Progress',
        ], escape: false);
});

it('shows the submitted information on the applicant\'s own profile', function () {
    $this->get(route('applicant.profile'))
        ->assertSeeInOrder([
            'Personal Information', 'Contact Information', 'Government and Tax', 'Documents', 'Application',
            'data-applicant-field="birthdate"', 'data-applicant-field="sex"', 'data-applicant-field="civilStatus"',
            'data-applicant-field="address"',
            'data-applicant-field="sssNumber"', 'data-applicant-field="birNumber"',
            'data-applicant-documents="education"', 'data-applicant-documents="workHistory"', 'data-applicant-documents="hiringRequirements"',
        ], escape: false);
});

it('keeps the applicant profile edit form limited to email and phone', function () {
    $html = $this->get(route('applicant.profile'))->getContent();
    $form = substr($html, strpos($html, 'data-applicant-form'));

    expect($form)->toContain('name="email"', 'name="phone"')
        ->and($form)->not->toContain('name="birthdate"', 'name="address"', 'name="sssNumber"', 'name="civilStatus"', 'type="file"');
});
