@props(['backUrl', 'backLabel'])

{{-- Generate Report: the error message, the way back to the module, and Generate (resources/js/report-generator.js). --}}
<div class="alert alert-danger mb-0 d-none" role="alert" data-report-error></div>

<div class="d-flex justify-content-end gap-2 flex-wrap">
    <a class="btn btn-outline-secondary" href="{{ $backUrl }}" data-report-back>{{ $backLabel }}</a>
    <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-report-generate>
        <span class="spinner-border spinner-border-sm d-none" aria-hidden="true" data-report-spinner></span>
        <span data-report-generate-label role="status">Generate PDF</span>
    </button>
</div>
