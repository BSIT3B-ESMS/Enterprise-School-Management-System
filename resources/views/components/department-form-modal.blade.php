{{--
    Add and edit form. Opened from Add College (data-department-add), or from the details modal's Edit button
    (data-department-edit="d…") to edit that college; handled by resources/js/departments.js. The name is chosen from the
    approved colleges not already listed.
--}}
<div class="modal fade" id="department-form-modal" tabindex="-1" aria-labelledby="department-form-modal-title"
    aria-hidden="true" data-department-form-modal>
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" novalidate data-department-form>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="department-form-modal-title" data-department-form-title>Add College</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <label for="department-name" class="form-label is-required">College</label>
                <select class="form-select" id="department-name" name="name" aria-describedby="department-name-error"
                    required></select>
                <div class="invalid-feedback" id="department-name-error"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
