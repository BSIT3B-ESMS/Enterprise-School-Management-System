{{--
    Delete confirmation. Opened from the details modal's Delete button (data-department-delete="d…");
    handled by resources/js/departments.js, which blocks it while employees, job titles, or applications are assigned.
--}}
<div class="modal fade" id="department-delete-modal" tabindex="-1" role="alertdialog"
    aria-labelledby="department-delete-modal-title" aria-describedby="department-delete-modal-body" aria-hidden="true"
    data-department-delete-modal>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="department-delete-modal-title">Delete college</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="department-delete-modal-body" data-department-delete-message></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" data-department-delete-confirm>Delete</button>
            </div>
        </div>
    </div>
</div>
