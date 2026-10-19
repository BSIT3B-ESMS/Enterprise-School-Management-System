{{--
    The HR Calendar's modals, driven by resources/js/calendar-scheduling.js: a date's events and notes, an event's
    details (venue, notes, and rescheduling history, with Reschedule and Cancel Schedule), the scheduling and rescheduling
    forms, and one confirmation modal that every change goes through (Confirm, Go Back to the form as it was, or Cancel).
    All interviews are on-site, so there is a venue and no meeting link.
--}}

{{-- A date: its events and their notes --}}
<div class="modal fade" id="calendar-day-modal" tabindex="-1" aria-labelledby="calendar-day-modal-title" aria-hidden="true"
    data-calendar-day-modal>
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="calendar-day-modal-title" data-calendar-day-modal-title></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group list-group-flush calendar-day-list" data-calendar-day-modal-events></div>
                <p class="text-body-secondary mb-0 d-none" data-calendar-day-modal-empty></p>
                <p class="small text-body-secondary mb-0 mt-2 d-none" data-calendar-day-modal-hidden></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-primary" data-calendar-day-add>Add Event</button>
                <button type="button" class="btn btn-primary" data-calendar-day-schedule>Schedule on This Date</button>
            </div>
        </div>
    </div>
</div>

{{-- An event's details --}}
<div class="modal fade" id="calendar-event-modal" tabindex="-1" aria-labelledby="calendar-event-modal-title" aria-hidden="true"
    data-calendar-event-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="min-w-0">
                    <h2 class="modal-title fs-5" id="calendar-event-modal-title" data-calendar-event-modal-title></h2>
                    <div class="text-body-secondary small" data-calendar-event-modal-subtitle></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-grid gap-4">
                <div class="alert alert-danger mb-0 d-none" role="alert" data-calendar-event-modal-error></div>
                <div data-calendar-event-modal-body></div>
            </div>
            <div class="modal-footer" data-calendar-event-modal-actions></div>
        </div>
    </div>
</div>

{{-- Add or edit a calendar event --}}
<div class="modal fade" id="calendar-event-form-modal" tabindex="-1" aria-labelledby="calendar-event-form-modal-title" aria-hidden="true"
    data-calendar-event-form-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" novalidate data-calendar-event-form>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="calendar-event-form-modal-title" data-event-form-title>Add Event</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary"><span class="text-danger fw-semibold" aria-hidden="true">*</span> Required field</p>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="calendar-event-title" class="form-label is-required">Event Title</label>
                        <input type="text" class="form-control" id="calendar-event-title" name="title" maxlength="120" autocomplete="off"
                            placeholder="e.g. Faculty Orientation" aria-describedby="calendar-event-title-error" required>
                        <div class="invalid-feedback" id="calendar-event-title-error" data-eventform-error="title"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="calendar-event-type" class="form-label is-required">Event Type</label>
                        <select class="form-select" id="calendar-event-type" name="type" aria-describedby="calendar-event-type-error" required></select>
                        <div class="invalid-feedback" id="calendar-event-type-error" data-eventform-error="type"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="calendar-event-status" class="form-label is-required">Status</label>
                        <select class="form-select" id="calendar-event-status" name="status" aria-describedby="calendar-event-status-error" required></select>
                        <div class="invalid-feedback" id="calendar-event-status-error" data-eventform-error="status"></div>
                    </div>

                    <div class="col-md-4">
                        <label for="calendar-event-date" class="form-label is-required">Date</label>
                        <input type="date" class="form-control" id="calendar-event-date" name="date"
                            aria-describedby="calendar-event-date-error" required>
                        <div class="invalid-feedback" id="calendar-event-date-error" data-eventform-error="date"></div>
                    </div>

                    <div class="col-md-4">
                        <label for="calendar-event-time" class="form-label is-required">Start Time</label>
                        <input type="time" class="form-control" id="calendar-event-time" name="time"
                            aria-describedby="calendar-event-time-error" required>
                        <div class="invalid-feedback" id="calendar-event-time-error" data-eventform-error="time"></div>
                    </div>

                    <div class="col-md-4">
                        <label for="calendar-event-end-time" class="form-label is-required">End Time</label>
                        <input type="time" class="form-control" id="calendar-event-end-time" name="endTime"
                            aria-describedby="calendar-event-end-time-error" required>
                        <div class="invalid-feedback" id="calendar-event-end-time-error" data-eventform-error="endTime"></div>
                    </div>

                    <div class="col-12">
                        <label for="calendar-event-venue" class="form-label is-required">Location / Venue</label>
                        <input type="text" class="form-control" id="calendar-event-venue" name="venue" maxlength="150" autocomplete="off"
                            placeholder="e.g. Main Hall" aria-describedby="calendar-event-venue-error" required>
                        <div class="invalid-feedback" id="calendar-event-venue-error" data-eventform-error="venue"></div>
                    </div>

                    <div class="col-12">
                        <label for="calendar-event-notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="calendar-event-notes" name="notes" rows="3" maxlength="500"
                            placeholder="Optional details or instructions"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Schedule an applicant --}}
<div class="modal fade" id="calendar-schedule-modal" tabindex="-1" aria-labelledby="calendar-schedule-modal-title" aria-hidden="true"
    data-calendar-schedule-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" novalidate data-calendar-schedule-form>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="calendar-schedule-modal-title">Schedule Applicant</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-body-secondary"><span class="text-danger fw-semibold" aria-hidden="true">*</span> Required field</p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="calendar-schedule-applicant" class="form-label is-required" data-schedule-applicant-label>Applicant Name</label>
                        <input type="text" class="form-control" id="calendar-schedule-applicant" readonly
                            aria-describedby="calendar-schedule-applicant-hint" data-schedule-applicant-name>
                        <select class="form-select d-none" id="calendar-schedule-applicant-select" name="applicationId"
                            aria-label="Applicant Name" aria-describedby="calendar-schedule-applicant-error" data-schedule-applicant-select></select>
                        <div class="form-text" id="calendar-schedule-applicant-hint" data-schedule-applicant-hint></div>
                        <div class="invalid-feedback" id="calendar-schedule-applicant-error" data-schedule-error="applicationId"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="calendar-schedule-type" class="form-label is-required">Interview Type</label>
                        <select class="form-select" id="calendar-schedule-type" name="type"
                            aria-describedby="calendar-schedule-type-error" required data-schedule-type></select>
                        <div class="invalid-feedback" id="calendar-schedule-type-error" data-schedule-error="type"></div>
                    </div>

                    <div class="col-md-4">
                        <label for="calendar-schedule-date" class="form-label is-required">Schedule Date</label>
                        <input type="date" class="form-control" id="calendar-schedule-date" name="date"
                            aria-describedby="calendar-schedule-date-error" required>
                        <div class="invalid-feedback" id="calendar-schedule-date-error" data-schedule-error="date"></div>
                    </div>

                    <div class="col-md-4">
                        <label for="calendar-schedule-time" class="form-label is-required">Start Time</label>
                        <input type="time" class="form-control" id="calendar-schedule-time" name="time"
                            aria-describedby="calendar-schedule-time-error" required>
                        <div class="invalid-feedback" id="calendar-schedule-time-error" data-schedule-error="time"></div>
                    </div>

                    <div class="col-md-4">
                        <label for="calendar-schedule-end-time" class="form-label is-required">End Time</label>
                        <input type="time" class="form-control" id="calendar-schedule-end-time" name="endTime"
                            aria-describedby="calendar-schedule-end-time-error" required>
                        <div class="invalid-feedback" id="calendar-schedule-end-time-error" data-schedule-error="endTime"></div>
                    </div>

                    <div class="col-12">
                        <label for="calendar-schedule-venue" class="form-label is-required">Location / Venue</label>
                        <input type="text" class="form-control" id="calendar-schedule-venue" name="venue" maxlength="150"
                            autocomplete="off" placeholder="e.g. HR Office, Main Building"
                            aria-describedby="calendar-schedule-venue-hint calendar-schedule-venue-error" required>
                        <div class="form-text" id="calendar-schedule-venue-hint">All interviews are held on-site.</div>
                        <div class="invalid-feedback" id="calendar-schedule-venue-error" data-schedule-error="venue"></div>
                    </div>

                    <div class="col-12">
                        <label for="calendar-schedule-notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="calendar-schedule-notes" name="notes" rows="3" maxlength="500"
                            placeholder="Optional details or instructions"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Schedule</button>
            </div>
        </form>
    </div>
</div>

{{-- Reschedule an event --}}
<div class="modal fade" id="calendar-reschedule-modal" tabindex="-1" aria-labelledby="calendar-reschedule-modal-title" aria-hidden="true"
    data-calendar-reschedule-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" novalidate data-calendar-reschedule-form>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="calendar-reschedule-modal-title">Reschedule</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row row-cols-1 row-cols-md-2 g-3 mb-4 employee-details">
                    <div class="col"><dt>Applicant</dt><dd class="mb-0" data-reschedule-field="applicant"></dd></div>
                    <div class="col"><dt>Current Schedule</dt><dd class="mb-0" data-reschedule-field="current"></dd></div>
                </dl>

                <p class="small text-body-secondary"><span class="text-danger fw-semibold" aria-hidden="true">*</span> Required field</p>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="calendar-reschedule-date" class="form-label is-required">New Date</label>
                        <input type="date" class="form-control" id="calendar-reschedule-date" name="date"
                            aria-describedby="calendar-reschedule-date-error" required>
                        <div class="invalid-feedback" id="calendar-reschedule-date-error" data-reschedule-error="date"></div>
                    </div>

                    <div class="col-md-4">
                        <label for="calendar-reschedule-time" class="form-label is-required">Start Time</label>
                        <input type="time" class="form-control" id="calendar-reschedule-time" name="time"
                            aria-describedby="calendar-reschedule-time-error" required>
                        <div class="invalid-feedback" id="calendar-reschedule-time-error" data-reschedule-error="time"></div>
                    </div>

                    <div class="col-md-4">
                        <label for="calendar-reschedule-end-time" class="form-label is-required">End Time</label>
                        <input type="time" class="form-control" id="calendar-reschedule-end-time" name="endTime"
                            aria-describedby="calendar-reschedule-end-time-error" required>
                        <div class="invalid-feedback" id="calendar-reschedule-end-time-error" data-reschedule-error="endTime"></div>
                    </div>

                    <div class="col-12">
                        <label for="calendar-reschedule-reason" class="form-label is-required">Reason for Rescheduling</label>
                        <select class="form-select" id="calendar-reschedule-reason" name="reason"
                            aria-describedby="calendar-reschedule-reason-error" required data-reschedule-reason></select>
                        <div class="invalid-feedback" id="calendar-reschedule-reason-error" data-reschedule-error="reason"></div>
                    </div>

                    <div class="col-12 d-none" data-reschedule-detail-field>
                        <label for="calendar-reschedule-detail" class="form-label is-required">Reason Details</label>
                        <textarea class="form-control" id="calendar-reschedule-detail" name="reasonDetail" rows="2" maxlength="300"
                            aria-describedby="calendar-reschedule-detail-error"></textarea>
                        <div class="invalid-feedback" id="calendar-reschedule-detail-error" data-reschedule-error="reasonDetail"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Confirmation: scheduling, rescheduling, or cancelling a schedule --}}
<div class="modal fade" id="calendar-confirm-modal" tabindex="-1" role="alertdialog" aria-labelledby="calendar-confirm-modal-title"
    aria-describedby="calendar-confirm-modal-body" aria-hidden="true" data-bs-backdrop="static" data-calendar-confirm-modal>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="calendar-confirm-modal-title" data-confirm-title></h2>
            </div>
            <div class="modal-body" id="calendar-confirm-modal-body">
                <p data-confirm-intro></p>
                <dl class="row row-cols-1 g-2 mb-0 employee-details" data-confirm-summary></dl>
                <div class="alert alert-danger mt-3 mb-0 d-none" role="alert" data-confirm-error></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary me-auto" data-confirm-cancel>Cancel</button>
                <button type="button" class="btn btn-outline-primary" data-confirm-back>Go Back</button>
                <button type="button" class="btn btn-primary" data-confirm-ok></button>
            </div>
        </div>
    </div>
</div>
