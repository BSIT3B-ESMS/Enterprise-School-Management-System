
{{-- Files leave for HR to approve (resources/js/portal-leave-request.js); it is Pending until HR decides. --}}
<div class="modal fade" id="leave-request-modal" tabindex="-1" aria-labelledby="leave-request-modal-title"
    aria-hidden="true" data-leave-request-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" novalidate data-leave-request-form>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="leave-request-modal-title">Request Leave</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger d-none" role="alert" data-leave-request-error></div>

                {{-- From the employee's own record (read-only). --}}
                <h3 class="employee-form-section">Employee</h3>
                <dl class="row row-cols-1 row-cols-sm-2 g-3 mb-4 employee-details">
                    <div class="col">
                        <dt>Name</dt>
                        <dd class="mb-0" data-leave-employee="name"></dd>
                    </div>
                    <div class="col">
                        <dt>Employee ID</dt>
                        <dd class="mb-0" data-leave-employee="id"></dd>
                    </div>
                    <div class="col">
                        <dt>College</dt>
                        <dd class="mb-0" data-leave-employee="department"></dd>
                    </div>
                    <div class="col">
                        <dt>Job Title</dt>
                        <dd class="mb-0" data-leave-employee="jobTitle"></dd>
                    </div>
                </dl>

                <h3 class="employee-form-section">Leave</h3>
                {{-- Available credits, the days requested, and the estimate if HR approves it (nothing is deducted until then). --}}
                <div class="alert alert-secondary py-2 small" role="status" aria-live="polite" data-leave-credit-note></div>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="leave-type" class="form-label is-required">Leave Type</label>
                        <select class="form-select" id="leave-type" name="type" aria-describedby="leave-type-error" required
                            data-leave-type-options></select>
                        <div class="invalid-feedback" id="leave-type-error"></div>
                    </div>

                    <div class="col-sm-6">
                        <label for="leave-start" class="form-label is-required">Start Date</label>
                        <input type="date" class="form-control" id="leave-start" name="startDate"
                            aria-describedby="leave-start-error" required>
                        <div class="invalid-feedback" id="leave-start-error"></div>
                    </div>

                    <div class="col-sm-6">
                        <label for="leave-end" class="form-label is-required">End Date</label>
                        <input type="date" class="form-control" id="leave-end" name="endDate"
                            aria-describedby="leave-end-error" required>
                        <div class="invalid-feedback" id="leave-end-error"></div>
                    </div>

                    <div class="col-12">
                        <label for="leave-reason" class="form-label is-required">Reason</label>
                        <textarea class="form-control" id="leave-reason" name="reason" rows="3" maxlength="500"
                            aria-describedby="leave-reason-error leave-reason-hint" required></textarea>
                        <div class="invalid-feedback" id="leave-reason-error"></div>
                        <div class="form-text" id="leave-reason-hint">Up to 500 characters. HR sees this with your request.</div>
                    </div>

                    {{-- Optional. Only each file's name and size are kept with the request; nothing is uploaded. --}}
                    <x-employee-document-field document="supportingDocuments" label="Supporting Documents (optional)" />
                </div>

                <p class="small text-body-secondary mt-3 mb-0">
                    <span class="text-danger fw-semibold" aria-hidden="true">*</span> Required. Your request is Pending until
                    HR approves or rejects it. You can cancel it while it is Pending.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>
