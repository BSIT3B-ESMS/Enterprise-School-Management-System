{{--
    An archived employee's details, opened from a row on the Archived Employees page (resources/js/archived-employees.js).
    Restore returns them to the Employees list; Delete opens the permanent delete confirmation
    (resources/js/employee-delete.js) once this modal has closed.
--}}
<div class="modal fade" id="archived-employee-details-modal" tabindex="-1" aria-labelledby="archived-employee-details-title"
    aria-hidden="true" data-archived-details-modal>
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="archived-employee-details-title" data-archived-details-title></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <dl class="row row-cols-1 row-cols-sm-2 g-3 mb-0 employee-details" data-archived-details-body></dl>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" data-employee-delete>Delete</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-archived-restore>Restore</button>
            </div>
        </div>
    </div>
</div>
