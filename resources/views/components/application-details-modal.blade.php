@php
    $fields = [
        'email' => 'Email Address',
        'phone' => 'Phone Number',
        'jobTitle' => 'Position Applied For',
        'department' => 'Department',
        'appliedOn' => 'Application Date',
        'status' => 'Application Status',
    ];
@endphp

{{--
    Application record and its recruitment workflow. Opened with Modal#show(row) passing a Recruitment table
    row carrying data-application-row="c…"; filled in and driven by resources/js/application-details.js.
    The status is never picked here: the Next Step buttons record milestones (a completed screening or
    interview, an issued or accepted offer, completed hiring, a rejection), and only those move the status.
    The footer offers only Delete and Edit, which hand the application ID on to those modals.
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

                <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                    @foreach ($fields as $field => $label)
                        <div class="col">
                            <dt>{{ $label }}</dt>
                            <dd class="mb-0" data-application-field="{{ $field }}"></dd>
                        </div>
                    @endforeach
                    <div class="col d-none" data-application-employee-row>
                        <dt>Employee Record</dt>
                        <dd class="mb-0"><a href="{{ route('employees.index') }}" data-application-employee-link></a></dd>
                    </div>
                </dl>

                <section aria-labelledby="application-progress-title">
                    <h3 class="employee-form-section" id="application-progress-title">Recruitment Progress</h3>
                    <ol class="recruitment-steps mb-0" data-application-steps></ol>
                </section>

                <section aria-labelledby="application-next-title">
                    <h3 class="employee-form-section" id="application-next-title" tabindex="-1" data-application-next>
                        Next Step <small>Status changes only when a milestone is reached</small>
                    </h3>

                    <p class="text-body-secondary mb-2" data-application-waiting></p>
                    <div class="d-flex flex-wrap gap-2" data-application-next-actions></div>

                    {{-- Inline form for scheduling or rescheduling a screening or interview. --}}
                    <form class="border rounded p-3 mt-3 d-none" novalidate data-application-activity-form>
                        <h4 class="fs-6 mb-3" data-application-activity-form-title></h4>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="application-activity-date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="application-activity-date" name="date"
                                    aria-describedby="application-activity-date-error" required data-application-activity-date>
                                <div class="invalid-feedback" id="application-activity-date-error"></div>
                            </div>

                            <div class="col-sm-6">
                                <label for="application-activity-time" class="form-label">Time</label>
                                <input type="time" class="form-control" id="application-activity-time" name="time"
                                    aria-describedby="application-activity-time-error" required>
                                <div class="invalid-feedback" id="application-activity-time-error"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-application-activity-cancel>Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm" data-application-activity-save>Save</button>
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

                <section aria-labelledby="application-activities-title">
                    <h3 class="employee-form-section" id="application-activities-title">
                        Screening and Interviews <small>Scheduled and completed activities</small>
                    </h3>
                    <ul class="list-group list-group-flush dashboard-list mb-0" data-application-activities></ul>
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

            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                    data-bs-target="#application-delete-modal" data-application-delete>Delete</button>

                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#application-form-modal" data-application-edit>Edit</button>
            </div>
        </div>
    </div>
</div>
