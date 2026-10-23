@props(['title' => 'Open positions', 'subtitle' => null])

{{--
    The open positions as cards (title, college, employment type, location, salary range, and a short description),
    with a search box, an Employment Type filter (Full-Time or Part-Time), and a college filter. Filled in by
    resources/js/applicant-positions.js from the job titles HR manages: a position is open while it has at least one
    opening. Used on the public PLP Careers page and in the Applicant interface.
--}}
<section class="d-grid gap-3" aria-labelledby="positions-open-title">
    <div>
        <h2 class="fs-4 mb-1 d-flex align-items-center gap-2" id="positions-open-title">
            {{ $title }} <span class="ems-chip tabular-nums" data-positions-open-count></span>
        </h2>
        @if ($subtitle)
            <p class="text-body-secondary mb-0">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="card employees-filters">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="positions-search" class="visually-hidden">Search positions</label>
                <div class="search-field">
                    <x-icon name="search" />
                    <input type="search" class="form-control" id="positions-search" placeholder="Search positions..."
                        autocomplete="off" data-positions-search>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <label for="positions-type" class="visually-hidden">Employment type</label>
                <select class="form-select" id="positions-type" data-positions-type>
                    <option value="">All Employment Types</option>
                    <option value="Full-time">Full-Time</option>
                    <option value="Part-time">Part-Time</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label for="positions-department" class="visually-hidden">College</label>
                <select class="form-select" id="positions-department" data-positions-department></select>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3" data-positions-open></div>

    <div class="card d-none" data-positions-none>
        <div class="employees-empty">
            <strong>No open positions</strong>
            There is nothing to apply for right now. Please check back later.
        </div>
    </div>

    <div class="card d-none" data-positions-no-match>
        <div class="employees-empty">
            <strong>No positions match your search</strong>
            Try another search or filter.
        </div>
    </div>
</section>
