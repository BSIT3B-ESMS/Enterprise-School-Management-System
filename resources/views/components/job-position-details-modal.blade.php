{{--
    A job posting's details, as the public Careers website shows them plus its status and recruitment numbers. Opened by
    clicking a posting's row (Modal#show(row) with data-job-position-row="t…") and filled in by resources/js/job-positions.js,
    which also points its Edit, Open or Close, and Remove buttons at the posting.
--}}
<div class="modal fade" id="job-position-details-modal" tabindex="-1" aria-labelledby="job-position-details-modal-title"
    aria-hidden="true" data-job-position-details-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="min-w-0">
                    <h2 class="modal-title fs-5" id="job-position-details-modal-title" data-job-position-field="name"></h2>
                    <div class="d-flex align-items-center gap-2 flex-wrap mt-1 text-body-secondary">
                        <span data-job-position-field="department"></span>
                        <span data-job-position-status></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body d-grid gap-4">
                <dl class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mb-0 employee-details">
                    <div class="col"><dt>Employment Type</dt><dd class="mb-0" data-job-position-field="employmentType"></dd></div>
                    <div class="col"><dt>Location</dt><dd class="mb-0" data-job-position-field="location"></dd></div>
                    <div class="col"><dt>Base Salary Range</dt><dd class="mb-0 tabular-nums" data-job-position-field="salary"></dd></div>
                    <div class="col"><dt>Openings</dt><dd class="mb-0 tabular-nums" data-job-position-field="openings"></dd></div>
                    <div class="col"><dt>Applications</dt><dd class="mb-0 tabular-nums" data-job-position-field="applicationCount"></dd></div>
                    <div class="col"><dt>Current Employees</dt><dd class="mb-0 tabular-nums" data-job-position-field="headcount"></dd></div>
                </dl>

                <section>
                    <h3 class="employee-form-section">Short Description</h3>
                    <p class="mb-0" data-job-position-field="summary"></p>
                </section>

                <section>
                    <h3 class="employee-form-section">Full Description</h3>
                    <p class="mb-0" data-job-position-field="description"></p>
                </section>

                @foreach (['responsibilities' => 'Responsibilities', 'qualifications' => 'Qualifications', 'requirements' => 'Requirements'] as $list => $heading)
                    <section>
                        <h3 class="employee-form-section">{{ $heading }}</h3>
                        <ul class="mb-0" data-job-position-list="{{ $list }}"></ul>
                    </section>
                @endforeach

                <section>
                    <h3 class="employee-form-section">Benefits</h3>
                    <div class="d-flex flex-wrap gap-2" data-job-position-benefits></div>
                </section>

                <a class="small d-none" href="{{ route('careers.index') }}" target="_blank" rel="noopener" data-job-position-public-link>
                    View this posting on the Careers website
                </a>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" data-job-position-remove>Remove</button>
                <button type="button" class="btn btn-outline-secondary" data-job-position-toggle></button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#job-position-form-modal" data-job-position-edit>Edit</button>
            </div>
        </div>
    </div>
</div>
