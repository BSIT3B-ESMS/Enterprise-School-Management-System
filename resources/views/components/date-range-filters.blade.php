@props(['name', 'subject'])

{{--
    Start Date, End Date, and Clear Filters for a list page's filter card (place inside its .row).
    $name prefixes the page's data attributes (data-{name}-filter="startDate" | "endDate", data-{name}-clear);
    $subject is the date being filtered, e.g. "Date Hired". Both dates are inclusive. The page's script
    reads the inputs and resources/js/date-filter.js keeps them consistent.
--}}
<div class="col-sm-6 col-md-4">
    <label for="{{ $name }}-start-date" class="form-label small mb-1">Start Date</label>
    <input type="date" class="form-control" id="{{ $name }}-start-date" title="Show records with a {{ $subject }} on or after this date"
        aria-describedby="{{ $name }}-date-error" data-{{ $name }}-filter="startDate" data-date-filter="start">
</div>

<div class="col-sm-6 col-md-4">
    <label for="{{ $name }}-end-date" class="form-label small mb-1">End Date</label>
    <input type="date" class="form-control" id="{{ $name }}-end-date" title="Show records with a {{ $subject }} on or before this date"
        aria-describedby="{{ $name }}-date-error" data-{{ $name }}-filter="endDate" data-date-filter="end">
</div>

<div class="col-md-4 d-flex align-items-end">
    <button type="button" class="btn btn-outline-secondary w-100" data-{{ $name }}-clear>Clear Filters</button>
</div>

<div class="col-12 text-danger small d-none mt-2" id="{{ $name }}-date-error" role="alert" data-date-range-error></div>
