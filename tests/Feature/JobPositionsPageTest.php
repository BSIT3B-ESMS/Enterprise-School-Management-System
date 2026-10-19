<?php

it('adds Open Job Positions to the HR sidebar, after Recruitment, and marks it current', function () {
    $this->get(route('job-positions.index'))
        ->assertOk()
        ->assertSee('aria-current="page" href="'.route('job-positions.index').'"', escape: false)
        ->assertSeeInOrder([
            'href="'.route('recruitment.index').'"', 'Recruitment',
            'href="'.route('job-positions.index').'"', 'Open Job Positions',
            'href="'.route('calendar.index').'"',
        ], escape: false);
});

it('lists the job postings with filters and sorting, and no Actions column', function () {
    $this->get(route('job-positions.index'))
        ->assertSee('data-page="job-positions"', escape: false)
        ->assertSeeInOrder([
            'Open Job Positions', 'Add Job Posting',
            'data-job-position-search', 'data-job-position-filter="departmentId"', 'data-job-position-filter="status"',
            'data-sort-key="name"', 'data-sort-key="department"', 'data-sort-key="employmentType"',
            'data-sort-key="salary"', 'data-sort-key="openings"', 'data-sort-key="status"',
            'data-job-position-rows', 'data-job-position-empty', 'data-job-position-pagination',
        ], escape: false);
});

it('has a posting form with the required details marked and the full job information', function () {
    $html = $this->get(route('job-positions.index'))->getContent();

    expect($html)->toContain('id="job-position-form-modal"')
        ->and($html)->toContain('name="name"', 'name="departmentId"', 'name="employmentType"', 'name="location"', 'name="salaryMin"', 'name="salaryMax"')
        ->and($html)->toContain('name="status"', 'name="openings"', 'name="summary"', 'name="description"')
        ->and($html)->toContain('name="responsibilities"', 'name="qualifications"', 'name="requirements"', 'data-job-position-benefit-options')
        ->and(substr_count($html, 'form-label is-required'))->toBe(12);
});

it('confirms removal in its own modal and keeps Remove out of data-bs-toggle so an in-use posting cannot open it', function () {
    $html = $this->get(route('job-positions.index'))->getContent();

    expect($html)->toContain('id="job-position-remove-modal"', 'data-job-position-remove-confirm')
        ->and($html)->toContain('data-job-position-remove>Remove</button>')
        ->and($html)->not->toContain('data-bs-target="#job-position-remove-modal"');
});

it('keeps the postings page free of the applicant interface', function () {
    $this->get(route('job-positions.index'))
        ->assertDontSee('data-applicant-', escape: false)
        ->assertDontSee('data-terms-modal', escape: false);
});

it('has no Actions column, and keeps Edit, Open or Close, and Remove in the details view', function () {
    $html = $this->get(route('job-positions.index'))->getContent();
    $table = substr($html, strpos($html, '<table'), strpos($html, '</table>') - strpos($html, '<table'));
    $details = substr($html, strpos($html, 'data-job-position-details-modal'));

    expect($table)->not->toContain('Actions')->not->toContain('<button type="button" class="btn btn-sm')
        ->and($html)->toContain('data-job-position-add')
        ->and($details)->toContain('data-job-position-remove>Remove</button>', 'data-job-position-toggle', 'data-job-position-edit>Edit</button>');
});

it('filters the postings by employment type, with a way to reset it', function () {
    $this->get(route('job-positions.index'))
        ->assertSeeInOrder([
            'data-job-position-filter="departmentId"',
            '<label for="job-position-type-filter" class="visually-hidden">Employment type</label>',
            'data-job-position-filter="employmentType"',
            'data-job-position-filter="status"',
            'data-job-position-clear>Clear Filters</button>',
            'data-job-position-rows',
        ], escape: false);
});

it('offers no Contractual or Internship employment type on the postings page', function () {
    $this->get(route('job-positions.index'))
        ->assertDontSee('Contractual')
        ->assertDontSee('Internship');
});

it('shows the salary range on the postings list and details, and edits it with a minimum and a maximum', function () {
    $html = $this->get(route('job-positions.index'))->getContent();

    expect($html)->toContain('Base Salary Range')
        ->and($html)->toContain('Minimum Base Salary', 'name="salaryMin"', 'Maximum Base Salary', 'name="salaryMax"')
        ->and($html)->not->toContain('name="salary"');
});
