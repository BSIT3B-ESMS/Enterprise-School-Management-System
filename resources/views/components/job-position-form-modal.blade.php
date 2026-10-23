{{--
    Add and edit form for a job posting. Opened from Add Job Posting (data-job-position-add) or the details
    view's Edit button (data-job-position-edit="t…"); handled by resources/js/job-positions.js. Required fields carry a red asterisk. The employment
    type options (Full-time and Part-time) are filled in by the script from the same list the filter uses.
--}}
<div class="modal fade" id="job-position-form-modal" tabindex="-1" aria-labelledby="job-position-form-modal-title"
    aria-hidden="true" data-job-position-form-modal>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" novalidate data-job-position-form>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="job-position-form-modal-title" data-job-position-form-title>Add Job Posting</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="small text-body-secondary"><span class="text-danger fw-semibold" aria-hidden="true">*</span> Required field</p>

                <section class="mb-4">
                    <h3 class="employee-form-section">Position</h3>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="job-position-name" class="form-label is-required">Job Title</label>
                            <input type="text" class="form-control" id="job-position-name" name="name" maxlength="100"
                                autocomplete="off" placeholder="e.g. Records Management Assistant"
                                aria-describedby="job-position-name-error" required>
                            <div class="invalid-feedback" id="job-position-name-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="job-position-department" class="form-label is-required">College</label>
                            <select class="form-select" id="job-position-department" name="departmentId"
                                aria-describedby="job-position-department-error" required data-job-position-department-options></select>
                            <div class="invalid-feedback" id="job-position-department-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="job-position-type" class="form-label is-required">Employment Type</label>
                            <select class="form-select" id="job-position-type" name="employmentType"
                                aria-describedby="job-position-type-error" required>
                                <option value="">Select employment type</option>
                            </select>
                            <div class="invalid-feedback" id="job-position-type-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="job-position-location" class="form-label is-required">Location</label>
                            <input type="text" class="form-control" id="job-position-location" name="location" maxlength="150"
                                autocomplete="off" placeholder="e.g. 12-B Alcalde Jose, Pasig City"
                                aria-describedby="job-position-location-error" required>
                            <div class="invalid-feedback" id="job-position-location-error"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="job-position-salary-min" class="form-label is-required">Minimum Base Salary (monthly, ₱)</label>
                            <input type="number" class="form-control" id="job-position-salary-min" name="salaryMin" min="0" step="0.01"
                                inputmode="decimal" aria-describedby="job-position-salary-min-error" required>
                            <div class="invalid-feedback" id="job-position-salary-min-error"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="job-position-salary-max" class="form-label is-required">Maximum Base Salary (monthly, ₱)</label>
                            <input type="number" class="form-control" id="job-position-salary-max" name="salaryMax" min="0" step="0.01"
                                inputmode="decimal" aria-describedby="job-position-salary-max-error" required>
                            <div class="invalid-feedback" id="job-position-salary-max-error"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="job-position-status" class="form-label is-required">Status</label>
                            <select class="form-select" id="job-position-status" name="status"
                                aria-describedby="job-position-status-hint job-position-status-error" required>
                                <option>Open</option>
                                <option>Closed</option>
                            </select>
                            <div class="form-text" id="job-position-status-hint">Only open postings appear on the Careers website.</div>
                            <div class="invalid-feedback" id="job-position-status-error"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="job-position-openings" class="form-label is-required">Number of Openings</label>
                            <input type="number" class="form-control" id="job-position-openings" name="openings" min="1" step="1"
                                inputmode="numeric" aria-describedby="job-position-openings-error" required>
                            <div class="invalid-feedback" id="job-position-openings-error"></div>
                        </div>
                    </div>
                </section>

                <section class="mb-4">
                    <h3 class="employee-form-section">Description</h3>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="job-position-summary" class="form-label is-required">Short Description</label>
                            <textarea class="form-control" id="job-position-summary" name="summary" rows="2" maxlength="200"
                                aria-describedby="job-position-summary-hint job-position-summary-error" required></textarea>
                            <div class="form-text" id="job-position-summary-hint">One or two sentences, shown on the job card. Up to 200 characters.</div>
                            <div class="invalid-feedback" id="job-position-summary-error"></div>
                        </div>

                        <div class="col-12">
                            <label for="job-position-description" class="form-label is-required">Full Description</label>
                            <textarea class="form-control" id="job-position-description" name="description" rows="4"
                                aria-describedby="job-position-description-error" required></textarea>
                            <div class="invalid-feedback" id="job-position-description-error"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="job-position-responsibilities" class="form-label is-required">Responsibilities</label>
                            <textarea class="form-control" id="job-position-responsibilities" name="responsibilities" rows="5"
                                aria-describedby="job-position-list-hint job-position-responsibilities-error" required></textarea>
                            <div class="invalid-feedback" id="job-position-responsibilities-error"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="job-position-qualifications" class="form-label is-required">Qualifications</label>
                            <textarea class="form-control" id="job-position-qualifications" name="qualifications" rows="5"
                                aria-describedby="job-position-list-hint job-position-qualifications-error" required></textarea>
                            <div class="invalid-feedback" id="job-position-qualifications-error"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="job-position-requirements" class="form-label">Requirements</label>
                            <textarea class="form-control" id="job-position-requirements" name="requirements" rows="5"
                                aria-describedby="job-position-list-hint"></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-text mt-0" id="job-position-list-hint">Write one item per line.</div>
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="employee-form-section">
                        Benefits <small>Offered with this position.</small>
                    </h3>

                    <div class="row row-cols-1 row-cols-md-2 g-2" data-job-position-benefit-options></div>
                </section>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
