{{-- Generate Report: the export format (PDF, Excel, or CSV), shared by every report page (resources/js/report-generator.js). --}}
<section class="card employee-form-card" aria-labelledby="report-format-title">
    <h2 class="employee-form-section" id="report-format-title">
        Export Format <small>Selected: <strong data-report-format-label>PDF</strong></small>
    </h2>

    <div class="btn-group w-100 report-formats" role="radiogroup" aria-labelledby="report-format-title">
        <input type="radio" class="btn-check" name="reportFormat" id="report-format-pdf" value="pdf" autocomplete="off" checked>
        <label class="btn btn-outline-primary" for="report-format-pdf">PDF</label>

        <input type="radio" class="btn-check" name="reportFormat" id="report-format-xlsx" value="xlsx" autocomplete="off">
        <label class="btn btn-outline-primary" for="report-format-xlsx">Excel (.xlsx)</label>

        <input type="radio" class="btn-check" name="reportFormat" id="report-format-csv" value="csv" autocomplete="off">
        <label class="btn btn-outline-primary" for="report-format-csv">CSV</label>
    </div>
    <p class="form-text mb-0 mt-2" data-report-format-hint></p>
</section>
