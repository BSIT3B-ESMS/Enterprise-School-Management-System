@props(['scope' => 'hr'])

@php
    $isHr = $scope === 'hr';
    $prefix = $isHr ? 'leave-requests' : 'my-leave-requests';
    $columns = array_merge(
        $isHr ? ['employeeName' => 'Employee'] : [],
        [
            'type' => 'Leave Type',
            'startDate' => 'Start Date',
            'endDate' => 'End Date',
            'filedOn' => 'Date Filed',
            'status' => 'Status',
        ],
    );
    // The same statuses and leave types as the shared mock data (leaveStatuses and getLeaveTypes in resources/js/mock-data.js).
    $statuses = ['Pending', 'Approved', 'Rejected', 'Cancelled'];
    $leaveTypes = ['Vacation Leave', 'Sick Leave', 'Emergency Leave'];
@endphp

{{--
    Leave requests from the one shared collection in the mock data, listed by resources/js/leave-requests.js.
    scope="hr" (Leave Requests page): every employee's requests; HR opens one to approve or reject
    it while it is Pending. scope="own" (Leave Request page): only the signed-in
    employee's requests, matched by employee ID; a Pending one can be cancelled, nothing else. Rejected and cancelled
    requests stay listed for reference.
--}}
<section class="d-grid gap-3" id="{{ $prefix }}" aria-labelledby="{{ $prefix }}-title" data-leave-requests="{{ $scope }}"
    @if ($isHr) data-employees-url="{{ url('/employees') }}" @endif>
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
    <div>
        <h2 class="fs-4 mb-0" id="{{ $prefix }}-title">{{ $isHr ? 'Leave Requests' : 'Leave Request' }}</h2>
        <p class="text-body-secondary mt-1 mb-0">
            {{ $isHr
                ? 'Every employee’s leave requests. Open a Pending request to approve or reject it.'
                : 'Your leave requests and HR’s decisions. You can cancel a request while it is Pending.' }}
        </p>
    </div>

    {{ $actions ?? '' }}
    </div>

    @unless ($isHr)
        {{-- The signed-in employee's leave credits (from their job title). Only Approved leave is counted as used. --}}
        <div class="card employee-form-card" data-leave-balance>
            <dl class="row row-cols-1 row-cols-sm-3 g-3 mb-0 employee-details">
                <div class="col"><dt>Leave Credits</dt><dd class="mb-0" data-leave-balance-field="credits"></dd></div>
                <div class="col"><dt>Used (Approved Leave)</dt><dd class="mb-0" data-leave-balance-field="used"></dd></div>
                <div class="col"><dt>Available</dt><dd class="mb-0 fw-semibold" data-leave-balance-field="remaining"></dd></div>
            </dl>
            <p class="small text-body-secondary mt-3 mb-0">
                Credits are counted in calendar days for the year. Pending requests do not use credits until HR approves them.
            </p>
        </div>
    @endunless

    <div class="card employees-filters">
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <label for="{{ $prefix }}-search" class="visually-hidden">Search leave requests</label>
                <div class="search-field">
                    <x-icon name="search" />
                    <input type="search" class="form-control" id="{{ $prefix }}-search"
                        placeholder="{{ $isHr ? 'Search employees, leave types, reasons...' : 'Search leave types, reasons, remarks...' }}"
                        autocomplete="off" data-leave-search>
                </div>
            </div>
            <div class="col-sm-6 col-md-3 col-xl-4">
                <label for="{{ $prefix }}-status-filter" class="visually-hidden">Status</label>
                <select class="form-select" id="{{ $prefix }}-status-filter" data-leave-filter="status">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-3 col-xl-4">
                <label for="{{ $prefix }}-type-filter" class="visually-hidden">Leave Type</label>
                <select class="form-select" id="{{ $prefix }}-type-filter" data-leave-filter="type">
                    <option value="">All Leave Types</option>
                    @foreach ($leaveTypes as $leaveType)
                        <option value="{{ $leaveType }}">{{ $leaveType }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-leave-clear>Clear Filters</button>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 employees-table">
                <thead>
                    <tr>
                        @foreach ($columns as $key => $label)
                            <th scope="col" aria-sort="none" data-leave-sort-column="{{ $key }}">
                                <button type="button" class="sort-button" data-leave-sort-key="{{ $key }}">
                                    {{ $label }}
                                    <x-icon name="chevron-up" class="sort-icon sort-icon-ascending" />
                                    <x-icon name="chevron-down" class="sort-icon sort-icon-descending" />
                                </button>
                            </th>
                        @endforeach
                        @unless ($isHr)
                            <th scope="col">HR Remarks</th>
                        @endunless
                    </tr>
                </thead>
                <tbody data-leave-rows></tbody>
            </table>
        </div>

        <div class="employees-empty d-none" data-leave-empty>
            <strong data-leave-empty-title></strong>
            <span data-leave-empty-hint></span>
        </div>

        <div class="employees-footer d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <span data-leave-summary></span>
            <nav aria-label="{{ $isHr ? 'Leave request pages' : 'My leave request pages' }}">
                <ul class="pagination pagination-sm mb-0" data-leave-pagination></ul>
            </nav>
        </div>
    </div>
</section>
