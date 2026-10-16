{{--
    Delete confirmation. Opened from the details modal's Delete button (data-job-title-delete="t…");
    handled by resources/js/job-titles.js, which blocks it while employees or applications use the job title.
--}}
<div class="modal fade" id="job-title-delete-modal" tabindex="-1" role="alertdialog"
    aria-labelledby="job-title-delete-modal-title" aria-describedby="job-title-delete-modal-body" aria-hidden="true"
    data-job-title-delete-modal>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="job-title-delete-modal-title">Delete job title</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="job-title-delete-modal-body" data-job-title-delete-message></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" data-job-title-delete-confirm>Delete</button>
            </div>
        </div>
    </div>
</div>
