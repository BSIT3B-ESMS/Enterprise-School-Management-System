{{--
    The open positions as cards (title, department, employment type, location, and a short description), with a
    search box and a department filter. Filled in by resources/js/applicant-positions.js from the job titles HR
    manages: a position is open while it has at least one opening. Used on the public Open Positions page and in the
    Applicant interface.
--}}
<div class="card employees-filters">
    <div class="row g-3">
        <div class="col-md-7">
            <label for="positions-search" class="visually-hidden">Search positions</label>
            <div class="search-field">
                <x-icon name="search" />
                <input type="search" class="form-control" id="positions-search" placeholder="Search positions..."
                    autocomplete="off" data-positions-search>
            </div>
        </div>
        <div class="col-md-5">
            <label for="positions-department" class="visually-hidden">Department</label>
            <select class="form-select" id="positions-department" data-positions-department></select>
        </div>
    </div>
</div>

<section aria-labelledby="positions-open-title">
    <h2 class="fs-5 mb-3 d-flex align-items-center gap-2" id="positions-open-title">
        Open positions <span class="ems-chip tabular-nums" data-positions-open-count></span>
    </h2>

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
            Try another search or department.
        </div>
    </div>
</section>
