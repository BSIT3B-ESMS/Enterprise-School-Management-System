{{--
    Add and edit form. Opened from Add Application (data-application-add), or from the details modal's
    Edit button (data-application-edit="c…") to edit that application; options, validation, and saving
    are handled by resources/js/recruitment.js. There is no status field: new applications start as Applied
    and the status then follows the recruitment workflow (see the details modal).
--}}
<div class="modal fade" id="application-form-modal" tabindex="-1" aria-labelledby="application-form-modal-title"
    aria-hidden="true" data-application-form-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" novalidate data-application-form>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="application-form-modal-title" data-application-form-title>Add Application</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <section class="mb-4">
                    <h3 class="employee-form-section">Applicant</h3>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="application-name" class="form-label">Applicant Name</label>
                            <input type="text" class="form-control" id="application-name" name="name" maxlength="120"
                                autocomplete="off" placeholder="e.g. Juan Dela Cruz"
                                aria-describedby="application-name-error" required>
                            <div class="invalid-feedback" id="application-name-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="application-email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="application-email" name="email" autocomplete="off"
                                placeholder="name@mail.com" aria-describedby="application-email-error" required>
                            <div class="invalid-feedback" id="application-email-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="application-phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="application-phone" name="phone" autocomplete="off"
                                placeholder="+63 9XX XXX XXXX" aria-describedby="application-phone-error" required>
                            <div class="invalid-feedback" id="application-phone-error"></div>
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="employee-form-section">Application</h3>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="application-department" class="form-label">Department</label>
                            <select class="form-select" id="application-department" name="departmentId"
                                aria-describedby="application-department-error" required
                                data-application-department-options></select>
                            <div class="invalid-feedback" id="application-department-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="application-job-title" class="form-label">Position Applied For</label>
                            <select class="form-select" id="application-job-title" name="jobTitleId"
                                aria-describedby="application-job-title-hint application-job-title-error" required
                                data-application-job-title-options></select>
                            <div class="form-text" id="application-job-title-hint">Lists the positions in the selected department.</div>
                            <div class="invalid-feedback" id="application-job-title-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="application-applied-on" class="form-label">Application Date</label>
                            <input type="date" class="form-control" id="application-applied-on" name="appliedOn"
                                aria-describedby="application-applied-on-error" required data-application-date>
                            <div class="invalid-feedback" id="application-applied-on-error"></div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
