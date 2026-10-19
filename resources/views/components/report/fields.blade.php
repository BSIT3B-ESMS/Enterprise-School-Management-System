{{-- Generate Report: the columns to include, in groups, filled in by resources/js/report-generator.js. --}}
<section class="card employee-form-card" aria-labelledby="report-fields-title">
    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
        <h2 class="employee-form-section mb-0" id="report-fields-title">
            Fields to Include <small data-report-field-count></small>
        </h2>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-report-select-all>Select All</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-report-deselect-all>Deselect All</button>
        </div>
    </div>

    <p class="form-text mt-0">Columns appear in the order listed. Missing values are left blank.</p>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" data-report-fields></div>

    <div class="alert alert-warning py-2 mt-3 mb-0 d-none" role="status" data-report-fields-error>
        Select at least one field to include in the report.
    </div>
</section>
