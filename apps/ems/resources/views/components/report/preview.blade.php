@props(['emptyTitle', 'emptyText'])

{{--
    Generate Report: how many records match, the criteria in words, and the first rows with the chosen fields, or the
    empty state when nothing matches (resources/js/report-generator.js). The slot says where to change what is included.
--}}
<section class="card employee-form-card" aria-labelledby="report-preview-title">
    <h2 class="employee-form-section" id="report-preview-title">Report Preview</h2>

    <p class="mb-1 fw-medium" data-report-count></p>
    <ul class="small text-body-secondary mb-2 ps-3" data-report-criteria></ul>
    @if ($slot->isNotEmpty())
        <p class="small mb-3">{{ $slot }}</p>
    @endif

    <div class="employees-empty border rounded d-none" data-report-empty>
        <strong>{{ $emptyTitle }}</strong>
        {{ $emptyText }}
    </div>

    <div class="d-none" data-report-preview>
        <div class="table-responsive border rounded">
            <table class="table table-sm align-middle mb-0 report-preview-table">
                <thead data-report-preview-head></thead>
                <tbody data-report-preview-rows></tbody>
            </table>
        </div>
        <p class="form-text mb-0" data-report-preview-note></p>
    </div>
</section>
