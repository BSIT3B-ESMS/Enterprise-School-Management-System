<?php

it('serves the applications page with Applications current in the applicant sidebar', function () {
    $this->get(route('applicant.applications'))
        ->assertOk()
        ->assertSee('data-page="applicant-applications"', escape: false)
        ->assertSee('data-details-url="'.route('applicant.application').'"', escape: false)
        ->assertSee('aria-current="page" href="'.route('applicant.applications').'"', escape: false);
});

it('lists position, submission date, status, and next step, with apply and browse actions', function () {
    $this->get(route('applicant.applications'))
        ->assertSeeInOrder([
            'Applications',
            'href="'.route('applicant.positions').'"', 'Browse Open Positions',
            'data-applications-apply', 'Apply for a Position',
            'data-applicant-missing', 'No applications found',
            'Position', 'Submitted', 'Status', 'Next Step',
            'data-applications-rows',
            'data-applications-empty', 'No applications yet',
            'data-applications-summary',
        ], escape: false);
});

it('gives the applicant no way to change a recruitment status', function () {
    $this->get(route('applicant.applications'))
        ->assertDontSee('<select', escape: false)
        ->assertDontSee('data-application-action', escape: false)
        ->assertDontSee('data-application-status', escape: false)
        ->assertDontSee('<form', escape: false)
        ->assertDontSee('Reject', escape: false)
        ->assertDontSee('Offer', escape: false);

    $this->get(route('applicant.application'))
        ->assertDontSee('<select', escape: false)
        ->assertDontSee('<form', escape: false)
        ->assertDontSee('data-application-action', escape: false);
});

it('offers a way back from the details page and keeps HR pages out', function () {
    $this->get(route('applicant.application'))
        ->assertSee('Back to Applications', escape: false)
        ->assertDontSee('href="'.route('recruitment.index').'"', escape: false)
        ->assertDontSee('href="'.route('employees.index').'"', escape: false);
});

it('lets a returning applicant go back to Applications from the application form and see their applications afterwards', function () {
    $this->get(route('applicant.apply'))
        ->assertSee('data-applications-url="'.route('applicant.applications').'"', escape: false)
        ->assertSee('data-apply-back', escape: false)
        ->assertSee('data-apply-back-label>Back to Sign In', escape: false)
        ->assertSee('data-apply-applications-link>View My Applications', escape: false);
});
