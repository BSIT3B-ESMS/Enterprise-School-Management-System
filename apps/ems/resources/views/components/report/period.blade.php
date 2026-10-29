@props(['id', 'legend', 'help'])

{{--
    Generate Report: a Date Range, picked the same way as the Employees Date Hired filter (a month, then optionally a From
    Date and To Date inside it), with Apply Filter and Clear Filter (resources/js/report-period.js).
--}}
<fieldset class="mt-3 pt-3 border-top" data-report-period>
    <legend class="form-label small fw-semibold mb-2">{{ $legend }}</legend>

    <div class="row g-3 align-items-start">
        <div class="col-md-6 col-xl-3">
            <label for="{{ $id }}-month" class="form-label">Month</label>
            <input type="month" class="form-control" id="{{ $id }}-month" placeholder="YYYY-MM"
                aria-describedby="{{ $id }}-month-error" data-period-month>
            <div class="invalid-feedback" id="{{ $id }}-month-error" data-period-error="month"></div>
        </div>
        <div class="col-md-6 col-xl-3">
            <label for="{{ $id }}-from" class="form-label">From Date</label>
            <input type="date" class="form-control" id="{{ $id }}-from" aria-describedby="{{ $id }}-from-error" data-period-from>
            <div class="invalid-feedback" id="{{ $id }}-from-error" data-period-error="from"></div>
        </div>
        <div class="col-md-6 col-xl-3">
            <label for="{{ $id }}-to" class="form-label">To Date</label>
            <input type="date" class="form-control" id="{{ $id }}-to" aria-describedby="{{ $id }}-to-error" data-period-to>
            <div class="invalid-feedback" id="{{ $id }}-to-error" data-period-error="to"></div>
        </div>
        <div class="col-md-6 col-xl-3 d-flex gap-2 align-self-xl-end">
            <button type="button" class="btn btn-primary" data-period-apply>Apply Filter</button>
            <button type="button" class="btn btn-outline-secondary" data-period-clear>Clear Filter</button>
        </div>
    </div>

    <p class="form-text mb-0 mt-2">{{ $help }} <span class="fw-medium" data-period-summary></span></p>
</fieldset>
