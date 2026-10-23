{{--
    Add and edit form. Opened from Add Job Title (data-job-title-add), or from the details modal's
    Edit button (data-job-title-edit="t…") to edit that job title; handled by resources/js/job-titles.js.
--}}
<div class="modal fade" id="job-title-form-modal" tabindex="-1" aria-labelledby="job-title-form-modal-title"
    aria-hidden="true" data-job-title-form-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" novalidate data-job-title-form>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="job-title-form-modal-title" data-job-title-form-title>Add Job Title</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="job-title-name" class="form-label">Job Title</label>
                        <input type="text" class="form-control" id="job-title-name" name="name" maxlength="100"
                            autocomplete="off" placeholder="e.g. Associate Professor"
                            aria-describedby="job-title-name-error" required>
                        <div class="invalid-feedback" id="job-title-name-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="job-title-department" class="form-label">College</label>
                        <select class="form-select" id="job-title-department" name="departmentId"
                            aria-describedby="job-title-department-error" required data-job-title-department-options></select>
                        <div class="invalid-feedback" id="job-title-department-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="job-title-salary-min" class="form-label">Minimum Base Salary (monthly, ₱)</label>
                        <input type="number" class="form-control" id="job-title-salary-min" name="salaryMin" min="0" step="0.01"
                            inputmode="decimal" aria-describedby="job-title-salary-min-error" required>
                        <div class="invalid-feedback" id="job-title-salary-min-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="job-title-salary-max" class="form-label">Maximum Base Salary (monthly, ₱)</label>
                        <input type="number" class="form-control" id="job-title-salary-max" name="salaryMax" min="0" step="0.01"
                            inputmode="decimal" aria-describedby="job-title-salary-max-error" required>
                        <div class="invalid-feedback" id="job-title-salary-max-error"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="job-title-leave-credits" class="form-label">Leave Credits (days per year)</label>
                        <input type="number" class="form-control" id="job-title-leave-credits" name="leaveCredits" min="0" max="365"
                            step="1" inputmode="numeric" aria-describedby="job-title-leave-credits-error job-title-leave-credits-hint" required>
                        <div class="invalid-feedback" id="job-title-leave-credits-error"></div>
                        <div class="form-text" id="job-title-leave-credits-hint">Employees with this title see it in their profile and when requesting leave.</div>
                    </div>
                </div>

                <section>
                    <h3 class="employee-form-section">
                        Benefits <small>New employees with this title start with these selected.</small>
                    </h3>

                    <div class="row row-cols-1 row-cols-md-2 g-2" data-job-title-benefit-options></div>
                </section>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
