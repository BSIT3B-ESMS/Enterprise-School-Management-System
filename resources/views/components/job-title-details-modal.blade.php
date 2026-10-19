@php
    $fields = [
        'department' => 'Department',
        'salary' => 'Base Salary Range (monthly)',
        'headcount' => 'Employees',
        'benefitCount' => 'Benefits',
    ];
@endphp

{{--
    Job title summary. Opened with Modal#show(row) passing a Job Titles table row carrying
    data-job-title-row="t…"; filled in by resources/js/job-titles.js.
    Its only actions are Edit and Delete, which hand the job title ID on to those modals.
--}}
<div class="modal fade" id="job-title-details-modal" tabindex="-1" aria-labelledby="job-title-details-modal-title"
    aria-hidden="true" data-job-title-details-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="job-title-details-modal-title" data-job-title-field="name"></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                    @foreach ($fields as $field => $label)
                        <div class="col">
                            <dt>{{ $label }}</dt>
                            <dd class="mb-0" data-job-title-field="{{ $field }}"></dd>
                        </div>
                    @endforeach
                </dl>

                <section class="mt-4">
                    <h3 class="employee-form-section">Benefits <small>Offered with this job title</small></h3>
                    <div class="d-flex flex-wrap gap-2" data-job-title-benefits></div>
                </section>

                <section class="mt-4">
                    <h3 class="employee-form-section">Recent Employees <small>Latest hires with this job title</small></h3>
                    <ul class="list-group list-group-flush dashboard-list" data-job-title-employees></ul>
                    <a class="d-inline-block mt-2" href="{{ route('employees.index') }}" data-job-title-employees-link>
                        View all in Employees
                    </a>
                </section>
            </div>

            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                    data-bs-target="#job-title-delete-modal" data-job-title-delete>Delete</button>

                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#job-title-form-modal" data-job-title-edit>Edit</button>
            </div>
        </div>
    </div>
</div>
