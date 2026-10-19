{{--
    Remove confirmation. Opened from the details view's Remove button carrying data-job-position-remove="t…" and handled by
    resources/js/job-positions.js. A posting that employees or applications still use is never offered for removal.
--}}
<div class="modal fade" id="job-position-remove-modal" tabindex="-1" role="alertdialog"
    aria-labelledby="job-position-remove-modal-title" aria-describedby="job-position-remove-modal-body" aria-hidden="true"
    data-job-position-remove-modal>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="job-position-remove-modal-title">Remove job posting</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="job-position-remove-modal-body" data-job-position-remove-message></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" data-job-position-remove-confirm>Remove</button>
            </div>
        </div>
    </div>
</div>
