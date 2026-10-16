@props(['headingTag' => 'h3'])

{{--
    Shared employee fields for the New Employee page and the Edit Employee modal.
    Options, defaults, validation, and submission are handled by resources/js/employee-form.js.
--}}
<section class="mb-4">
    <{{ $headingTag }} class="employee-form-section">Personal and Contact</{{ $headingTag }}>

    <div class="row g-3">
        <div class="col-md-6">
            <label for="employee-name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="employee-name" name="name" autocomplete="off"
                aria-describedby="employee-name-error" required>
            <div class="invalid-feedback" id="employee-name-error"></div>
        </div>

        <div class="col-md-6">
            <label for="employee-birthdate" class="form-label">Birthdate</label>
            <input type="date" class="form-control" id="employee-birthdate" name="birthdate">
        </div>

        <div class="col-md-6">
            <label for="employee-sex" class="form-label">Sex</label>
            <select class="form-select" id="employee-sex" name="sex">
                <option>Female</option>
                <option>Male</option>
                <option>Prefer not to say</option>
            </select>
        </div>

        <div class="col-md-6">
            <label for="employee-civil-status" class="form-label">Civil Status</label>
            <select class="form-select" id="employee-civil-status" name="civilStatus">
                <option>Single</option>
                <option>Married</option>
                <option>Widowed</option>
                <option>Separated</option>
            </select>
        </div>

        <div class="col-md-6">
            <label for="employee-email" class="form-label">Email</label>
            <input type="email" class="form-control" id="employee-email" name="email" autocomplete="off"
                aria-describedby="employee-email-error" required>
            <div class="invalid-feedback" id="employee-email-error"></div>
        </div>

        <div class="col-md-6">
            <label for="employee-phone" class="form-label">Phone</label>
            <input type="tel" class="form-control" id="employee-phone" name="phone" placeholder="+63 9XX XXX XXXX">
        </div>

        <div class="col-12">
            <label for="employee-address" class="form-label">Address</label>
            <input type="text" class="form-control" id="employee-address" name="address">
        </div>
    </div>
</section>

<section class="mb-4">
    <{{ $headingTag }} class="employee-form-section">Employment</{{ $headingTag }}>

    <div class="row g-3">
        <div class="col-md-6">
            <label for="employee-id" class="form-label">Employee ID</label>
            <input type="text" class="form-control" id="employee-id" readonly data-employee-id>
        </div>

        <div class="col-md-6">
            <label for="employee-department" class="form-label">Department</label>
            <select class="form-select" id="employee-department" name="departmentId"
                aria-describedby="employee-department-error" required data-department-options></select>
            <div class="invalid-feedback" id="employee-department-error"></div>
        </div>

        <div class="col-md-6">
            <label for="employee-job-title" class="form-label">Job Title</label>
            <select class="form-select" id="employee-job-title" name="jobTitleId"
                aria-describedby="employee-job-title-error" required data-job-title-options></select>
            <div class="invalid-feedback" id="employee-job-title-error"></div>
        </div>

        <div class="col-md-6">
            <label for="employee-hired-on" class="form-label">Date Hired</label>
            <input type="date" class="form-control" id="employee-hired-on" name="hiredOn"
                aria-describedby="employee-hired-on-error" required>
            <div class="invalid-feedback" id="employee-hired-on-error"></div>
        </div>

        <div class="col-md-6">
            <label for="employee-salary" class="form-label">Base Salary (monthly, ₱)</label>
            <input type="number" class="form-control" id="employee-salary" name="salary" min="0" step="0.01"
                inputmode="decimal" aria-describedby="employee-salary-hint employee-salary-error" required>
            <div class="form-text" id="employee-salary-hint">Defaults from the selected job title. You may edit it.</div>
            <div class="invalid-feedback" id="employee-salary-error"></div>
        </div>
    </div>
</section>

<section>
    <{{ $headingTag }} class="employee-form-section">
        Benefits <small>Pre-selected from the job title. You may edit them.</small>
    </{{ $headingTag }}>

    <div class="row row-cols-1 row-cols-md-2 g-2" data-benefit-options></div>
</section>
