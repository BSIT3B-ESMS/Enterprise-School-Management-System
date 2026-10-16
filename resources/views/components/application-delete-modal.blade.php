{{--
    Delete confirmation. Opened from the details modal's Delete button (data-application-delete="c…");
    handled by resources/js/recruitment.js.
--}}
<div class="modal fade" id="application-delete-modal" tabindex="-1" role="alertdialog"
    aria-labelledby="application-delete-modal-title" aria-describedby="application-delete-modal-body" aria-hidden="true"
    data-application-delete-modal>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="application-delete-modal-title">Delete application</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="application-delete-modal-body" data-application-delete-message></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" data-application-delete-confirm>Delete</button>
            </div>
        </div>
    </div>
</div>
