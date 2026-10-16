@php
    $fields = [
        'jobTitle' => 'Job Title',
        'department' => 'Department',
        'hiredOn' => 'Date Hired',
        'salary' => 'Base Salary (monthly)',
        'email' => 'Email',
        'phone' => 'Phone',
        'birthdate' => 'Birthdate',
        'sex' => 'Sex',
        'civilStatus' => 'Civil Status',
        'address' => 'Address',
        'benefits' => 'Benefits',
    ];
@endphp

{{--
    Employee record. Open it with data-bs-toggle="modal" data-bs-target="#employee-details-modal"
    on an element carrying data-employee-id, or with Modal#show(element) passing such an element
    (as the Employees table rows do); filled in by resources/js/employee-details.js.
--}}
<div class="modal fade" id="employee-details-modal" tabindex="-1" aria-labelledby="employee-details-modal-title"
    aria-hidden="true" data-employee-details-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="employee-details-modal-title" data-employee-field="name"></h2>
                    <span class="ems-chip tabular-nums" data-employee-field="id"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <dl class="row row-cols-1 row-cols-md-2 g-3 mb-0 employee-details">
                    @foreach ($fields as $field => $label)
                        <div class="col">
                            <dt>{{ $label }}</dt>
                            <dd class="mb-0" data-employee-field="{{ $field }}"></dd>
                        </div>
                    @endforeach
                </dl>

                <section class="mt-4 d-none" data-employee-history-section>
                    <h3 class="employee-form-section">History <small>Recent changes to this record</small></h3>
                    <ul class="list-group list-group-flush dashboard-list" data-employee-history></ul>
                </section>
            </div>

            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                    data-bs-target="#employee-delete-modal" data-employee-delete>Delete</button>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#employee-form-modal" data-employee-edit>Edit</button>
                </div>
            </div>
        </div>
    </div>
</div>
