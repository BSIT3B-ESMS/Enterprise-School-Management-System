<?php

it('serves the applications page with Applications current in the applicant sidebar', function () {
    $this->get(route('applicant.applications'))
        ->assertOk()
        ->assertSee('data-page="applicant-applications"', escape: false)
        ->assertSee('data-details-url="'.route('applicant.applications').'"', escape: false)
        ->assertSee('aria-current="page" href="'.route('applicant.applications').'"', escape: false);
});

it('lists position, submission date, status, and next step, with a way to apply for another position', function () {
    $this->get(route('applicant.applications'))
        ->assertSeeInOrder([
            'Applications',
            'href="'.route('applicant.positions').'"', 'Apply for a Position',
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

    $this->get(route('applicant.applications.show', 'c1'))
        ->assertDontSee('<select', escape: false)
        ->assertDontSee('<form', escape: false)
        ->assertDontSee('data-application-action', escape: false);
});

it('offers a way back from the details page, a link to the position, and keeps HR pages out', function () {
    $this->get(route('applicant.applications.show', 'c1'))
        ->assertSee('Back to Applications', escape: false)
        ->assertSee('data-position-url="'.route('applicant.positions').'"', escape: false)
        ->assertSee('data-applicant-position-link>View Position', escape: false)
        ->assertDontSee('href="'.route('recruitment.index').'"', escape: false)
        ->assertDontSee('href="'.route('employees.index').'"', escape: false);
});
