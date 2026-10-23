{{--
    College summary. Opened with Modal#show(row) passing a Colleges table row carrying
    data-department-row="d…"; filled in by resources/js/departments.js.
    Its only actions are Edit and Delete, which hand the college ID on to those modals.
--}}
<div class="modal fade" id="department-details-modal" tabindex="-1" aria-labelledby="department-details-modal-title"
    aria-hidden="true" data-department-details-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="department-details-modal-title" data-department-field="name"></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                    <div class="col">
                        <dt>No. of Faculty</dt>
                        <dd class="mb-0 tabular-nums" data-department-field="headcount"></dd>
                    </div>
                    <div class="col">
                        <dt>Job Titles</dt>
                        <dd class="mb-0 tabular-nums" data-department-field="jobTitleCount"></dd>
                    </div>
                </dl>

                <section class="mt-4">
                    <h3 class="employee-form-section">Job Titles <small>Positions under this college</small></h3>
                    <ul class="list-group list-group-flush dashboard-list" data-department-job-titles></ul>
                </section>

                <section class="mt-4">
                    <h3 class="employee-form-section">Recent Faculty <small>Latest hires in this college</small></h3>
                    <ul class="list-group list-group-flush dashboard-list" data-department-faculty></ul>
                    <a class="d-inline-block mt-2" href="{{ route('employees.index') }}" data-department-employees-link>
                        View all faculty in Employees
                    </a>
                </section>
            </div>

            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                    data-bs-target="#department-delete-modal" data-department-delete>Delete</button>

                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#department-form-modal" data-department-edit>Edit</button>
            </div>
        </div>
    </div>
</div>
