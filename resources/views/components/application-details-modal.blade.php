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
    Application record. Opened with Modal#show(row) passing a Recruitment table row carrying
    data-application-row="c…"; filled in by resources/js/recruitment.js.
    Its only actions are Edit and Delete, which hand the application ID on to those modals.
--}}
<div class="modal fade" id="application-details-modal" tabindex="-1" aria-labelledby="application-details-modal-title"
    aria-hidden="true" data-application-details-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="application-details-modal-title" data-application-field="name"></h2>
                    <span class="status-pill" data-application-status-pill></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                    @foreach ($fields as $field => $label)
                        <div class="col">
                            <dt>{{ $label }}</dt>
                            <dd class="mb-0" data-application-field="{{ $field }}"></dd>
                        </div>
                    @endforeach
                </dl>

                <section class="mt-4 d-none" data-application-history-section>
                    <h3 class="employee-form-section">History <small>Recent changes to this application</small></h3>
                    <ul class="list-group list-group-flush dashboard-list" data-application-history></ul>
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
