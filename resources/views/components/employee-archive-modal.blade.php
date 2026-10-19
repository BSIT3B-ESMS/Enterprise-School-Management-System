{{--
    Archive confirmation. Opened from a trigger carrying data-employee-archive="EMP-…"; handled by
    resources/js/employee-archive.js. Archiving keeps the employee's information and moves them to Archived Employees,
    where they can be restored or deleted.
--}}
<div class="modal fade" id="employee-archive-modal" tabindex="-1" role="alertdialog"
    aria-labelledby="employee-archive-modal-title" aria-describedby="employee-archive-modal-body" aria-hidden="true"
    data-employee-archive-modal>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="employee-archive-modal-title">Archive employee</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="employee-archive-modal-body" data-employee-archive-message></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" data-employee-archive-confirm>Archive</button>
            </div>
        </div>
    </div>
</div>
