@extends('layouts.applicant')

@section('title', 'Open Positions · '.config('app.name'))

{{--
    The open positions in the Applicant interface, from the same job titles HR manages (resources/js/applicant-positions.js).
    No pay figures are passed to this page.
--}}
@section('content')
    <div class="d-grid gap-4" data-page="applicant-positions" data-positions-context="portal"
        data-details-url="{{ route('applicant.positions') }}"
        data-apply-url="{{ route('applicant.apply') }}">
        <div>
            <h1 class="page-title">Open Positions</h1>
            <p class="text-body-secondary mt-1 mb-0">Browse the open positions and apply for the ones that suit you.</p>
        </div>

        <x-applicant-position-list />
    </div>
@endsection
