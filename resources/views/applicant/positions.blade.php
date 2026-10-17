@extends('layouts.applicant')

@section('title', 'Open Positions · '.config('app.name'))

{{--
    Every position, open and closed, filled in by resources/js/applicant-positions.js from the same job title data HR
    manages (a position is open while it has at least one opening). Open positions can be viewed and applied for;
    closed ones can be viewed but have no way to apply. No pay figures are passed to this page.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="applicant-positions" data-apply-url="{{ route('applicant.apply') }}">
        <div>
            <h1 class="page-title">Open Positions</h1>
            <p class="text-body-secondary mt-1 mb-0">Browse the positions and apply for the ones that are open.</p>
        </div>

        <div class="card d-none" data-positions-none>
            <div class="employees-empty">
                <strong>No positions available</strong>
                There are no positions to show right now. Please check back later.
            </div>
        </div>

        <div class="d-grid gap-4" data-positions-content>
            <section aria-labelledby="positions-open-title">
                <h2 class="fs-5 mb-3 d-flex align-items-center gap-2" id="positions-open-title">
                    Open positions <span class="ems-chip tabular-nums" data-positions-open-count></span>
                </h2>

                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" data-positions-open></div>
                <p class="text-body-secondary mb-0 d-none" data-positions-open-empty>No positions are open right now.</p>
            </section>

            <section aria-labelledby="positions-closed-title" data-positions-closed-section>
                <h2 class="fs-5 mb-1" id="positions-closed-title">Closed positions</h2>
                <p class="dashboard-panel-subtitle">Not accepting applications</p>

                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" data-positions-closed></div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="position-details-modal" tabindex="-1" aria-labelledby="position-details-modal-title"
        aria-hidden="true" data-position-details-modal>
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="min-w-0">
                        <h2 class="modal-title fs-5" id="position-details-modal-title" data-position-field="name"></h2>
                        <div class="d-flex align-items-center gap-2 flex-wrap mt-1 text-body-secondary">
                            <span data-position-field="department"></span>
                            <span data-position-status></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <section class="mb-4">
                        <h3 class="employee-form-section">About the position</h3>
                        <p class="mb-0" data-position-field="description"></p>
                    </section>

                    <section class="mb-4">
                        <h3 class="employee-form-section">Qualifications</h3>
                        <ul class="mb-0" data-position-qualifications></ul>
                        <p class="text-body-secondary mb-0 d-none" data-position-qualifications-empty>No qualifications are listed.</p>
                    </section>

                    <section>
                        <h3 class="employee-form-section">Benefits</h3>
                        <div class="d-flex flex-wrap gap-2" data-position-benefits></div>
                    </section>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <a class="btn btn-primary" href="{{ route('applicant.apply') }}" data-position-apply>Apply for this Position</a>
                    <button type="button" class="btn btn-primary d-none" disabled data-position-closed>Applications closed</button>
                </div>
            </div>
        </div>
    </div>
@endsection
