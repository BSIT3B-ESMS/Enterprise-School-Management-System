@php
    $tabs = [
        'applicant' => 'Applicant Information',
        'application' => 'Application Details',
        'schedule' => 'Demo and Final Interviews',
        'offer' => 'Offer and Hiring',
    ];

    $applicant = [
        'lastName' => 'Last Name',
        'firstName' => 'First Name',
        'middleName' => 'Middle Name',
        'email' => 'Email Address',
        'phone' => 'Phone Number',
        'birthdate' => 'Birthdate',
        'sex' => 'Sex',
        'civilStatus' => 'Civil Status',
    ];
    $application = [
        'jobTitle' => 'Position Applied For',
        'department' => 'College',
        'appliedOn' => 'Application Date',
        'status' => 'Application Status',
    ];
    $offer = [
        'offerSalary' => 'Monthly Salary',
        'offerStartDate' => 'Start Date',
        'offerResponse' => 'Applicant’s Response',
        'offerIssuedOn' => 'Issued',
    ];
    $government = [
        'sssNumber' => 'SSS Number',
        'philhealthNumber' => 'PhilHealth Number',
        'pagibigNumber' => 'Pag-IBIG (HDMF) Number',
        'tin' => 'Tax Identification Number (TIN)',
        'birNumber' => 'BIR Number',
    ];
    $documents = [
        'hiringRequirements' => 'Hiring Requirements',
        'education' => 'Education Transcripts or Diplomas',
        'workHistory' => 'Work History',
    ];
@endphp

{{--
    Application record and its recruitment workflow. Opened with Modal#show(row) passing a Recruitment table row carrying
    data-application-row="c…"; filled in and driven by resources/js/application-details.js. Next Step (above the tabs)
    holds what HR can do now; the tabs show the applicant, the application, the demo interview and final interview
    schedule, and the offer and hiring. The status is never picked here: only completed milestones move it (a completed
    demo interview or final interview, an offer HR issues, the applicant's own answer to it and pre-employment
    information, completed hiring, a rejection). What an applicant submitted is read-only for HR, so Edit is offered only
    for applications HR added. The footer offers Delete (with confirmation) and Edit.
--}}
<div class="modal fade" id="application-details-modal" tabindex="-1" aria-labelledby="application-details-modal-title"
    aria-hidden="true" data-application-details-modal data-employees-url="{{ url('/employees') }}">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="application-details-modal-title" data-application-field="name"></h2>
                    <span class="status-pill" data-application-status-pill></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body d-grid gap-4">
                <div class="alert alert-danger mb-0 d-none" role="alert" data-application-flash></div>

                <section aria-labelledby="application-next-title">
                    <h3 class="employee-form-section" id="application-next-title" tabindex="-1" data-application-next>
                        Next Step <small>Status changes only when a milestone is reached</small>
                    </h3>

                    <p class="text-body-secondary mb-2" data-application-waiting></p>
                    <div class="d-flex flex-wrap gap-2" data-application-next-actions></div>

                    {{-- Inline form for scheduling or rescheduling a demo interview or final interview. --}}
                    <form class="border rounded p-3 mt-3 d-none" novalidate data-application-activity-form>
                        <h4 class="fs-6 mb-3" data-application-activity-form-title></h4>

                        <div class="row g-3">
                            <div class="col-sm-4">
                                <label for="application-activity-date" class="form-label is-required">Date</label>
                                <input type="date" class="form-control" id="application-activity-date" name="date"
                                    aria-describedby="application-activity-date-error" required data-application-activity-date>
                                <div class="invalid-feedback" id="application-activity-date-error"></div>
                            </div>

                            <div class="col-sm-4">
                                <label for="application-activity-time" class="form-label is-required">Start Time</label>
                                <input type="time" class="form-control" id="application-activity-time" name="time"
                                    aria-describedby="application-activity-time-error" required>
                                <div class="invalid-feedback" id="application-activity-time-error"></div>
                            </div>

                            <div class="col-sm-4">
                                <label for="application-activity-end-time" class="form-label is-required">End Time</label>
                                <input type="time" class="form-control" id="application-activity-end-time" name="endTime"
                                    aria-describedby="application-activity-end-time-error" required>
                                <div class="invalid-feedback" id="application-activity-end-time-error"></div>
                            </div>

                            <div class="col-12" data-application-activity-venue-field>
                                <label for="application-activity-venue" class="form-label is-required">Venue</label>
                                <input type="text" class="form-control" id="application-activity-venue" name="venue" maxlength="120"
                                    placeholder="e.g. HR Office, Room 204" aria-describedby="application-activity-venue-error" required>
                                <div class="invalid-feedback" id="application-activity-venue-error"></div>
                            </div>

                            <div class="col-sm-6 d-none" data-application-activity-reason-field>
                                <label for="application-activity-reason" class="form-label is-required">Reason for Rescheduling</label>
                                <select class="form-select" id="application-activity-reason" name="reason"
                                    aria-describedby="application-activity-reason-error" required data-application-activity-reasons></select>
                                <div class="invalid-feedback" id="application-activity-reason-error"></div>
                            </div>

                            <div class="col-sm-6 d-none" data-application-activity-reason-detail-field>
                                <label for="application-activity-reason-detail" class="form-label is-required">Describe the Reason</label>
                                <input type="text" class="form-control" id="application-activity-reason-detail" name="reasonDetail" maxlength="200"
                                    aria-describedby="application-activity-reason-detail-error">
                                <div class="invalid-feedback" id="application-activity-reason-detail-error"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-application-activity-cancel>Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm" data-application-activity-save>Save</button>
                        </div>
                    </form>

                    {{-- Inline form for issuing or updating an offer. --}}
                    <form class="border rounded p-3 mt-3 d-none" novalidate data-application-offer-form>
                        <h4 class="fs-6 mb-1" data-application-offer-form-title></h4>
                        <p class="small text-body-secondary mb-3" data-application-offer-range></p>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="application-offer-salary" class="form-label is-required">Monthly Salary (₱)</label>
                                <input type="number" class="form-control tabular-nums" id="application-offer-salary" name="salary" min="0" step="0.01"
                                    inputmode="decimal" aria-describedby="application-offer-salary-error" required>
                                <div class="invalid-feedback" id="application-offer-salary-error"></div>
                            </div>

                            <div class="col-sm-6">
                                <label for="application-offer-start-date" class="form-label is-required">Start Date</label>
                                <input type="date" class="form-control" id="application-offer-start-date" name="startDate"
                                    aria-describedby="application-offer-start-date-error" required>
                                <div class="invalid-feedback" id="application-offer-start-date-error"></div>
                            </div>

                            <div class="col-12">
                                <label for="application-offer-notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="application-offer-notes" name="notes" rows="2" maxlength="500"
                                    aria-describedby="application-offer-notes-error"></textarea>
                                <div class="invalid-feedback" id="application-offer-notes-error"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-application-offer-cancel>Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm" data-application-offer-save></button>
                        </div>
                    </form>

                    {{-- Inline confirmation for steps that cannot be undone. --}}
                    <div class="alert alert-warning mt-3 mb-0 d-none" role="alertdialog" aria-labelledby="application-confirm-message"
                        data-application-confirm>
                        <p class="mb-2" id="application-confirm-message" data-application-confirm-message></p>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-application-confirm-no>Go back</button>
                            <button type="button" class="btn btn-sm btn-primary" data-application-confirm-yes></button>
                        </div>
                    </div>
                </section>

                <div>
                    <ul class="nav nav-underline profile-tabs mb-3" role="tablist">
                        @foreach ($tabs as $key => $label)
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link{{ $loop->first ? ' active' : '' }}" id="application-{{ $key }}-tab"
                                    data-bs-toggle="tab" data-bs-target="#application-{{ $key }}-pane" role="tab"
                                    aria-controls="application-{{ $key }}-pane" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                    data-application-tab="{{ $key }}">{{ $label }}</button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content">
                        <section class="tab-pane fade show active" id="application-applicant-pane" role="tabpanel"
                            aria-labelledby="application-applicant-tab" tabindex="0">
                            <p class="small text-body-secondary" data-application-source></p>
                            <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                                @foreach ($applicant as $field => $label)
                                    <div class="col">
                                        <dt>{{ $label }}</dt>
                                        <dd class="mb-0 text-break" data-application-field="{{ $field }}"></dd>
                                    </div>
                                @endforeach
                                <div class="col-12">
                                    <dt>Address</dt>
                                    <dd class="mb-0" data-application-field="address"></dd>
                                </div>
                            </dl>
                        </section>

                        <section class="tab-pane fade" id="application-application-pane" role="tabpanel"
                            aria-labelledby="application-application-tab" tabindex="0">
                            <div class="d-grid gap-4">
                                <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                                    @foreach ($application as $field => $label)
                                        <div class="col">
                                            <dt>{{ $label }}</dt>
                                            <dd class="mb-0" data-application-field="{{ $field }}"></dd>
                                        </div>
                                    @endforeach
                                </dl>

                                <section aria-labelledby="application-documents-title">
                                    <h3 class="employee-form-section" id="application-documents-title">Documents</h3>
                                    <div class="d-grid gap-3">
                                        @foreach ($documents as $key => $label)
                                            <div>
                                                <div class="fw-medium mb-1">{{ $label }}</div>
                                                <ul class="list-group document-list" data-application-documents="{{ $key }}"></ul>
                                                <p class="small text-body-secondary mb-0" data-application-documents-empty="{{ $key }}">No files attached.</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>

                                <section aria-labelledby="application-progress-title">
                                    <h3 class="employee-form-section" id="application-progress-title">Recruitment Progress</h3>
                                    <ol class="recruitment-steps mb-0" data-application-steps></ol>
                                </section>

                                <section aria-labelledby="application-milestones-title">
                                    <h3 class="employee-form-section" id="application-milestones-title">
                                        Completed Milestones <small>Each one moved the application forward</small>
                                    </h3>
                                    <ul class="list-group list-group-flush dashboard-list mb-0" data-application-milestones></ul>
                                </section>

                                <section class="d-none" data-application-history-section>
                                    <h3 class="employee-form-section">History <small>Recent changes to this application</small></h3>
                                    <ul class="list-group list-group-flush dashboard-list mb-0" data-application-history></ul>
                                </section>
                            </div>
                        </section>

                        <section class="tab-pane fade" id="application-schedule-pane" role="tabpanel"
                            aria-labelledby="application-schedule-tab" tabindex="0">
                            <h3 class="employee-form-section">
                                Demo Interview and Final Interview <small>Scheduled activities; completing one is the milestone</small>
                            </h3>
                            <ul class="list-group list-group-flush dashboard-list mb-0" data-application-activities></ul>
                        </section>

                        <section class="tab-pane fade" id="application-offer-pane" role="tabpanel"
                            aria-labelledby="application-offer-tab" tabindex="0">
                            <div class="d-grid gap-4">
                                <section aria-labelledby="application-offer-title">
                                    <h3 class="employee-form-section" id="application-offer-title">Offer</h3>
                                    <p class="text-body-secondary mb-0" data-application-offer-empty>No offer has been issued yet.</p>
                                    <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details d-none" data-application-offer-details>
                                        @foreach ($offer as $field => $label)
                                            <div class="col">
                                                <dt>{{ $label }}</dt>
                                                <dd class="mb-0" data-application-field="{{ $field }}"></dd>
                                            </div>
                                        @endforeach
                                        <div class="col-12">
                                            <dt>Notes</dt>
                                            <dd class="mb-0" data-application-field="offerNotes"></dd>
                                        </div>
                                    </dl>
                                </section>

                                <section aria-labelledby="application-pre-employment-title">
                                    <h3 class="employee-form-section" id="application-pre-employment-title">
                                        Pre-employment Information <small>Given by the applicant after accepting the offer</small>
                                    </h3>
                                    <p class="text-body-secondary mb-0" data-application-pre-employment-empty>Not submitted yet.</p>
                                    <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details d-none" data-application-pre-employment>
                                        <div class="col"><dt>Emergency Contact</dt><dd class="mb-0" data-application-field="emergencyContactName"></dd></div>
                                        <div class="col"><dt>Emergency Contact Number</dt><dd class="mb-0" data-application-field="emergencyContactNumber"></dd></div>
                                        <div class="col"><dt>Relationship</dt><dd class="mb-0" data-application-field="emergencyContactRelationship"></dd></div>
                                        @foreach ($government as $field => $label)
                                            <div class="col">
                                                <dt>{{ $label }}</dt>
                                                <dd class="mb-0 tabular-nums" data-application-field="{{ $field }}"></dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </section>

                                <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details d-none" data-application-employee-row>
                                    <div class="col">
                                        <dt>Employee Record</dt>
                                        <dd class="mb-0"><a href="{{ route('employees.index') }}" data-application-employee-link></a></dd>
                                    </div>
                                </dl>
                            </div>
                        </section>
                    </div>
                </div>
            </div>

            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                    data-bs-target="#application-delete-modal" data-application-delete>Delete</button>

                <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                    <small class="text-body-secondary d-none" data-application-edit-note>Submitted by the applicant: read-only for HR.</small>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#application-form-modal" data-application-edit>Edit</button>
                </div>
            </div>
        </div>
    </div>
</div>
