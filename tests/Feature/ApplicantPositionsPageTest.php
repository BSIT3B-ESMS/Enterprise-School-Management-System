<?php

it('serves the open positions page with Open Positions current in the applicant sidebar', function () {
    $this->get(route('applicant.positions'))
        ->assertOk()
        ->assertSee('data-page="applicant-positions"', escape: false)
        ->assertSee('data-apply-url="'.route('applicant.apply').'"', escape: false)
        ->assertSee('aria-current="page" href="'.route('applicant.positions').'"', escape: false);
});

it('has open and closed sections and an empty state', function () {
    $this->get(route('applicant.positions'))
        ->assertSeeInOrder([
            'Open Positions',
            'data-positions-none', 'No positions available',
            'Open positions', 'data-positions-open-count', 'data-positions-open>',
            'data-positions-open-empty', 'No positions are open right now.',
            'Closed positions', 'Not accepting applications', 'data-positions-closed>',
        ], escape: false);
});

it('has a details modal with the description, qualifications, benefits, and an apply action', function () {
    $this->get(route('applicant.positions'))
        ->assertSeeInOrder([
            'id="position-details-modal"',
            'data-position-field="name"', 'data-position-field="department"', 'data-position-status',
            'About the position', 'data-position-field="description"',
            'Qualifications', 'data-position-qualifications',
            'Benefits', 'data-position-benefits',
            'Close', 'data-position-apply>Apply for this Position', 'data-position-closed', 'Applications closed',
        ], escape: false);
});

it('shows no pay on the positions page and keeps HR pages out of it', function () {
    $this->get(route('applicant.positions'))
        ->assertDontSee('salary', escape: false)
        ->assertDontSee('Salary', escape: false)
        ->assertDontSee('₱', escape: false)
        ->assertDontSee('href="'.route('employees.index').'"', escape: false)
        ->assertDontSee('href="'.route('recruitment.index').'"', escape: false);
});

it('refuses a closed position on the application form and prefills from the applicant', function () {
    $this->get(route('applicant.apply'))
        ->assertSeeInOrder(['data-apply-position-notice', 'not open for applications', 'data-apply-form'], escape: false);
});
