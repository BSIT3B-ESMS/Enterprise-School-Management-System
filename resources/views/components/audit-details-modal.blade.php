@php
    $fields = [
        'action' => 'Action',
        'at' => 'Date and Time',
        'user' => 'User',
        'recordType' => 'Record Type',
        'record' => 'Affected Record',
        'detail' => 'Details',
    ];
@endphp

{{--
    Audit entry. Opened with Modal#show(row) passing an Audit Logs table row carrying
    data-audit-row="l…"; filled in by resources/js/audit-logs.js. Entries are read-only,
    so the modal only closes.
--}}
<div class="modal fade" id="audit-details-modal" tabindex="-1" aria-labelledby="audit-details-modal-title"
    aria-hidden="true" data-audit-details-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="audit-details-modal-title" data-audit-field="title"></h2>
                    <span class="status-pill" data-audit-action-pill></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                    @foreach ($fields as $field => $label)
                        <div class="col">
                            <dt>{{ $label }}</dt>
                            <dd class="mb-0" data-audit-field="{{ $field }}"></dd>
                        </div>
                    @endforeach
                </dl>

                <section class="mt-4 d-none" data-audit-related-section>
                    <h3 class="employee-form-section">Other activity <small>Recent entries for the same record</small></h3>
                    <ul class="list-group list-group-flush dashboard-list" data-audit-related></ul>
                </section>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
