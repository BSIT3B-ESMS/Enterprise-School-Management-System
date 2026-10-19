@props(['position', 'backUrl'])

{{--
    One position's full details: description, responsibilities, qualifications, requirements, and benefits, with
    Apply Now. Filled in by resources/js/applicant-positions.js from the job titles HR manages. A closed position
    (no openings) can be read but not applied for, and an applicant who already applied is pointed to that
    application instead. Used on the public job page and in the Applicant interface.
--}}
<div class="d-grid gap-4" data-position-details data-position-id="{{ $position }}">
    <div>
        <a class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ $backUrl }}">
            <x-icon name="back" />
            Back to Open Positions
        </a>
    </div>

    <div class="card d-none" data-position-missing>
        <div class="employees-empty">
            <strong>Position not found</strong>
            This position is no longer listed. Browse the open positions instead.
        </div>
    </div>

    <div class="d-grid gap-4 d-none" data-position-content>
        <section class="card employee-form-card">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div class="min-w-0">
                    <h1 class="page-title" data-position-field="name"></h1>
                    <div class="d-flex align-items-center gap-2 flex-wrap mt-1 text-body-secondary">
                        <span data-position-field="department"></span>
                        <span data-position-status></span>
                    </div>
                </div>

                <div class="d-grid gap-2 text-md-end" data-position-actions>
                    <a class="btn btn-primary" href="#" data-position-apply>Apply Now</a>
                    <button type="button" class="btn btn-primary d-none" disabled data-position-closed>Applications closed</button>
                    <a class="btn btn-outline-primary d-none" href="#" data-position-view-application>View My Application</a>
                </div>
            </div>

            <p class="alert mb-0 mt-3 d-none" role="status" data-position-notice></p>

            <dl class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mt-1 mb-0 employee-details">
                <div class="col"><dt>Base Salary Range</dt><dd class="mb-0 tabular-nums" data-position-field="salary"></dd></div>
                <div class="col"><dt>Employment Type</dt><dd class="mb-0" data-position-field="employmentType"></dd></div>
                <div class="col"><dt>Location</dt><dd class="mb-0" data-position-field="location"></dd></div>
                <div class="col"><dt>Openings</dt><dd class="mb-0 tabular-nums" data-position-field="openings"></dd></div>
            </dl>
        </section>

        <section class="card employee-form-card">
            <section class="mb-4">
                <h2 class="employee-form-section">About the Position</h2>
                <p class="mb-0" data-position-field="description"></p>
            </section>

            @foreach (['responsibilities' => 'Responsibilities', 'qualifications' => 'Qualifications', 'requirements' => 'Requirements'] as $list => $heading)
                <section class="mb-4">
                    <h2 class="employee-form-section">{{ $heading }}</h2>
                    <ul class="mb-0" data-position-list="{{ $list }}"></ul>
                    <p class="text-body-secondary mb-0 d-none" data-position-list-empty="{{ $list }}">None listed.</p>
                </section>
            @endforeach

            <section>
                <h2 class="employee-form-section">Benefits</h2>
                <div class="d-flex flex-wrap gap-2" data-position-benefits></div>
            </section>
        </section>
    </div>
</div>
