@extends('layouts.employee')

@section('title', 'Benefits · '.config('app.name'))

{{--
    Benefits for the signed-in employee, filled in by resources/js/portal-benefits.js from the mock data: each
    benefit's description, the job titles it is offered with, and whether it is included for them. No pay
    figures or other employees' details are passed to this page.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="portal-benefits">
        <div>
            <h1 class="page-title">Benefits</h1>
            <p class="text-body-secondary mt-1 mb-0">What is included for you, and what else is offered.</p>
        </div>

        <div class="card d-none" data-portal-missing>
            <div class="employees-empty">
                <strong>Employee record not found</strong>
                Your record may have been removed. Contact HR for help.
            </div>
        </div>

        <div class="card d-none" data-benefits-none>
            <div class="employees-empty">
                <strong>No benefits available</strong>
                There are no benefits to show right now.
            </div>
        </div>

        <div class="d-grid gap-4" data-portal-content>
            <section aria-labelledby="benefits-yours-title" data-benefits-yours-section>
                <h2 class="fs-5 mb-3 d-flex align-items-center gap-2" id="benefits-yours-title">
                    Your benefits <span class="ems-chip tabular-nums" data-benefits-yours-count></span>
                </h2>

                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" data-benefits-yours></div>

                <div class="d-none" data-benefits-yours-empty>
                    <x-profile-empty-state title="No benefits included yet">
                        None are included for you at the moment.
                    </x-profile-empty-state>
                </div>
            </section>

            <section aria-labelledby="benefits-other-title" data-benefits-other-section>
                <h2 class="fs-5 mb-1" id="benefits-other-title">Other benefits</h2>
                <p class="dashboard-panel-subtitle">Not included for you; see who they are offered to.</p>

                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" data-benefits-other></div>
            </section>
        </div>
    </div>
@endsection
