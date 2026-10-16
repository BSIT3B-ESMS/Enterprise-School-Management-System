{{--
    Delete confirmation. Opened from a trigger carrying data-employee-delete="EMP-…";
    handled by resources/js/employee-details.js.
--}}
<div class="modal fade" id="employee-delete-modal" tabindex="-1" role="alertdialog"
    aria-labelledby="employee-delete-modal-title" aria-describedby="employee-delete-modal-body" aria-hidden="true"
    data-employee-delete-modal>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="employee-delete-modal-title">Delete employee</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="employee-delete-modal-body" data-employee-delete-message></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" data-employee-delete-confirm>Delete</button>
            </div>
        </div>
    </div>
</div>
