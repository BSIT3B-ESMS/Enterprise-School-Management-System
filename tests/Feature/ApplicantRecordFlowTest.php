<?php

beforeEach(fn () => $this->actingAs(hrHead()));

it('organizes the job application form into the 201 file sections', function () {
    $this->get(route('applicant.apply'))
        ->assertSeeInOrder([
            'data-apply-form',
            'Personal Information', 'data-apply-account="name"', 'name="birthdate"', 'name="sex"', 'name="civilStatus"',
            'Position Applied For', 'name="jobTitleId"', 'data-apply-position-summary', 'data-document-field="hiringRequirements"',
            'Contact Information', 'data-apply-account="email"', 'name="phone"', 'name="address"',
            'Government and Tax', 'name="sssNumber"', 'name="philhealthNumber"', 'name="pagibigNumber"', 'name="tin"', 'name="birNumber"',
            'Education and Work History', 'data-document-field="education"', 'data-document-field="workHistory"',
            'Submit Application',
        ], escape: false);
});

it('takes the name and email from the applicant\'s account', function () {
    expect($this->get(route('applicant.apply'))->getContent())
        ->toContain('id="apply-name" readonly', 'id="apply-email" readonly')
        ->not->toContain('name="name"')
        ->not->toContain('name="email"');
});

it('uses the employee form\'s labels, dropdown options, and input types', function () {
    $this->get(route('applicant.apply'))
        ->assertSeeInOrder([
            'for="apply-sex"', '<option>Female</option>', '<option>Male</option>', '<option>Prefer not to say</option>',
            'for="apply-civil-status"', '<option>Single</option>', '<option>Married</option>', '<option>Widowed</option>', '<option>Separated</option>',
        ], escape: false)
        ->assertSee('type="date" class="form-control" id="apply-birthdate"', escape: false)
        ->assertSeeInOrder(['SSS Number', 'PhilHealth Number', 'Pag-IBIG (HDMF) Number', 'Tax Identification Number (TIN)', 'BIR Number'])
        ->assertSeeInOrder(['Hiring Requirements', 'Education Transcripts or Diplomas', 'Work History']);
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

it('shows the submitted information on the applicant\'s own profile and application', function (string $route) {
    $this->get(route($route, ['application' => 'c1']))
        ->assertSeeInOrder([
            'data-applicant-field="birthdate"', 'data-applicant-field="sex"', 'data-applicant-field="civilStatus"',
            'data-applicant-field="address"',
            'data-applicant-documents="hiringRequirements"', 'data-applicant-documents="education"', 'data-applicant-documents="workHistory"',
        ], escape: false);
})->with(['applicant.profile', 'applicant.applications.show']);

it('keeps documents out of the applicant profile edit form, since they are added when applying', function () {
    $html = $this->get(route('applicant.profile'))->getContent();
    $form = substr($html, strpos($html, 'data-applicant-form'));

    expect($form)->toContain('name="email"', 'name="phone"', 'name="birthdate"')
        ->and($form)->not->toContain('type="file"', 'data-document-field');
});

it('asks for a required emergency contact on the job application form only', function () {
    $this->get(route('applicant.apply'))
        ->assertSeeInOrder([
            'Contact Information', 'name="phone"',
            'Emergency Contact',
            'name="emergencyContactName"', 'name="emergencyContactNumber"', 'name="emergencyContactRelationship"',
            'Government and Tax',
        ], escape: false);

    foreach ([route('applicant.register'), route('applicant.profile')] as $url) {
        $this->get($url)->assertDontSee('emergencyContact', escape: false);
    }
});

it('shows the emergency contact on the applicant\'s application and on HR\'s applicant record', function () {
    $this->get(route('applicant.applications.show', 'c1'))
        ->assertSeeInOrder(['data-applicant-field="emergencyContactName"', 'data-applicant-field="emergencyContactNumber"', 'data-applicant-field="emergencyContactRelationship"'], escape: false);

    $this->get(route('recruitment.index'))
        ->assertSeeInOrder(['Emergency Contact', 'data-application-field="emergencyContactName"', 'data-application-field="emergencyContactNumber"', 'data-application-field="emergencyContactRelationship"', 'Government and Tax'], escape: false);
});

it('masks the phone and government number fields and shows their formats', function (string $route, string $prefix) {
    $this->get(route($route))
        ->assertSee('id="'.$prefix.'-phone"', escape: false)
        ->assertSee('data-mask="phone"', escape: false)
        ->assertSee('Mobile number: 09XXXXXXXXX or +639XXXXXXXXX', escape: false)
        ->assertSee('data-mask="sss" placeholder="00-1234567-8"', escape: false)
        ->assertSee('data-mask="philhealth" placeholder="12-123456789-0"', escape: false)
        ->assertSee('data-mask="pagibig" placeholder="1234-5678-9012"', escape: false)
        ->assertSee('data-mask="tin" placeholder="123-456-789"', escape: false)
        ->assertSee('Format: 123-456-789 (9 digits), or 123-456-789-000 with a branch code', escape: false)
        // The BIR number has no fixed format, so it keeps the existing digits-and-dashes check without a mask.
        ->assertDontSee('data-mask="bir"', escape: false);
})->with([['applicant.apply', 'apply'], ['applicant.profile', 'applicant']]);

it('offers Save Draft separately from Submit Application on the job application form', function () {
    $this->get(route('applicant.apply'))
        ->assertSeeInOrder([
            'data-apply-draft-notice',
            'data-apply-form',
            'type="button" class="btn btn-outline-primary" data-apply-save>Save Draft</button>',
            'type="submit" class="btn btn-primary">Submit Application</button>',
        ], escape: false);
});

it('lists saved drafts on the dashboard, apart from the submitted applications', function () {
    $this->get(route('applicant.dashboard'))
        ->assertSeeInOrder([
            'data-applicant-drafts-panel', 'Saved drafts', 'Not submitted yet', 'data-applicant-drafts',
            'Your applications', 'data-applicant-application-list',
        ], escape: false)
        ->assertSee('data-apply-url="'.route('applicant.apply').'"', escape: false);
});

it('keeps drafts out of HR Recruitment', function () {
    $this->get(route('recruitment.index'))
        ->assertDontSee('data-apply-save', escape: false)
        ->assertDontSee('data-applicant-draft', escape: false);
});
