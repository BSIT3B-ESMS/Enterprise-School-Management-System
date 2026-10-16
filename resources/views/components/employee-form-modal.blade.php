{{--
    Edit form. Opened from a trigger carrying data-employee-edit="EMP-…" (the details modal's Edit button).
    New employees are added on the full-page form (employees.create).
--}}
<div class="modal fade" id="employee-form-modal" tabindex="-1" aria-labelledby="employee-form-modal-title"
    aria-hidden="true" data-employee-form-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" novalidate data-employee-form>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="employee-form-modal-title">Edit Employee</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <x-employee-form-fields />
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
