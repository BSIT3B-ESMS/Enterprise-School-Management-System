<?php

it('organizes the job application form into the 201 file sections that apply when applying', function () {
    $this->get(route('applicant.apply'))
        ->assertSeeInOrder([
            'data-apply-form',
            'Position Applied For', 'name="jobTitleId"', 'data-apply-position-summary', 'data-document-field="hiringRequirements"',
            'Personal Information', 'data-apply-account="lastName"', 'data-apply-account="firstName"', 'data-apply-account="middleName"',
            'name="birthdate"', 'name="sex"', 'name="civilStatus"',
            'Contact Information', 'data-apply-account="email"', 'name="phone"', 'name="address"',
            'Education and Work History', 'data-document-field="education"', 'data-document-field="workHistory"',
            'Submit Application',
        ], escape: false);
});

it('takes the name (in parts) and email from the applicant\'s account', function () {
    expect($this->get(route('applicant.apply'))->getContent())
        ->toContain('id="apply-last-name" readonly', 'id="apply-first-name" readonly', 'id="apply-middle-name" readonly', 'id="apply-email" readonly')
        ->not->toContain('name="name"')
        ->not->toContain('name="email"');
});

it('returns Back and Cancel to the chosen position\'s details', function () {
    $html = $this->get(route('applicant.apply'))->getContent();

    expect(substr_count($html, 'data-apply-back'))->toBe(3)
        ->and($html)->toContain('data-positions-url="'.route('applicant.positions').'"', 'data-apply-back-label>Back to Open Positions');
});

it('uses the employee form\'s labels, dropdown options, and input types', function () {
    $this->get(route('applicant.apply'))
        ->assertSeeInOrder([
            'for="apply-sex"', '<option>Female</option>', '<option>Male</option>', '<option>Prefer not to say</option>',
            'for="apply-civil-status"', '<option>Single</option>', '<option>Married</option>', '<option>Widowed</option>', '<option>Separated</option>',
        ], escape: false)
        ->assertSee('type="date" class="form-control" id="apply-birthdate"', escape: false)
        ->assertSeeInOrder(['Hiring Requirements', 'Education Transcripts or Diplomas', 'Work History']);
});

it('uses the existing file field for documents, without asking for employment-stage records', function () {
    $this->get(route('applicant.apply'))
        ->assertSee('type="file" class="visually-hidden" id="employee-documents-education" multiple', escape: false)
        ->assertSeeInOrder(['for="employee-documents-education" aria-hidden="true">Choose Files', 'data-document-status>No file chosen'], escape: false)
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
            'Applicant Information', 'data-application-field="lastName"', 'data-application-field="firstName"',
            'data-application-field="middleName"', 'data-application-field="birthdate"', 'data-application-field="sex"',
            'data-application-field="civilStatus"', 'data-application-field="address"',
            'Documents', 'data-application-documents="hiringRequirements"', 'data-application-documents="education"',
            'data-application-documents="workHistory"',
            'Recruitment Progress',
            'Pre-employment Information', 'data-application-field="sssNumber"', 'data-application-field="philhealthNumber"',
            'data-application-field="pagibigNumber"', 'data-application-field="tin"', 'data-application-field="birNumber"',
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

it('asks for the emergency contact and government numbers only as pre-employment information, after an offer is accepted', function () {
    $this->get(route('applicant.applications.show', 'c1'))
        ->assertSeeInOrder([
            'Job Offer', 'data-applicant-offer-respond="Accepted">Accept Offer', 'data-applicant-offer-respond="Declined">Decline Offer',
            'Pre-employment Information', 'data-applicant-pre-employment-form',
            'Emergency Contact', 'name="emergencyContactName"', 'name="emergencyContactNumber"', 'name="emergencyContactRelationship"',
            'Government and Tax', 'name="sssNumber"', 'name="philhealthNumber"', 'name="pagibigNumber"', 'name="tin"', 'name="birNumber"',
        ], escape: false);

    foreach ([route('applicant.apply'), route('applicant.register'), route('applicant.profile')] as $url) {
        $this->get($url)
            ->assertDontSee('emergencyContact', escape: false)
            ->assertDontSee('sssNumber', escape: false);
    }
});

it('shows the pre-employment information on HR\'s applicant record, under Offer and Hiring', function () {
    $this->get(route('recruitment.index'))
        ->assertSeeInOrder([
            'Offer and Hiring', 'Offer', 'data-application-field="offerSalary"', 'data-application-field="offerStartDate"',
            'Pre-employment Information', 'data-application-field="emergencyContactName"', 'data-application-field="emergencyContactNumber"',
            'data-application-field="emergencyContactRelationship"', 'data-application-field="sssNumber"',
        ], escape: false);
});

it('masks the phone fields and shows their format', function (string $route, string $prefix) {
    $this->get(route($route))
        ->assertSee('id="'.$prefix.'-phone"', escape: false)
        ->assertSee('data-mask="phone"', escape: false)
        ->assertSee('Mobile number: 09XXXXXXXXX or +639XXXXXXXXX', escape: false);
})->with([['applicant.apply', 'apply'], ['applicant.profile', 'applicant']]);

it('masks the pre-employment government number fields and shows their formats', function () {
    $this->get(route('applicant.applications.show', 'c1'))
        ->assertSee('id="pre-employment-emergency-number"', escape: false)
        ->assertSee('data-mask="phone"', escape: false)
        ->assertSee('data-mask="sss" placeholder="00-1234567-8"', escape: false)
        ->assertSee('data-mask="philhealth" placeholder="12-123456789-0"', escape: false)
        ->assertSee('data-mask="pagibig" placeholder="1234-5678-9012"', escape: false)
        ->assertSee('data-mask="tin" placeholder="123-456-789"', escape: false)
        ->assertSee('Format: 123-456-789 (9 digits), or 123-456-789-000 with a branch code', escape: false)
        // The BIR number has no fixed format, so it keeps the existing digits-and-dashes check without a mask.
        ->assertDontSee('data-mask="bir"', escape: false);
});

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
