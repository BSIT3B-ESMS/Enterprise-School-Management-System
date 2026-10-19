@extends('layouts.applicant')

@section('title', 'Position Details · '.config('app.name'))

{{--
    One position's details in the Applicant interface. Apply Now opens the application form for it, unless it is
    closed or the applicant already applied (resources/js/applicant-positions.js).
--}}
@section('content')
    <div data-page="applicant-position" data-positions-context="portal" data-apply-url="{{ route('applicant.apply') }}"
        data-application-url="{{ route('applicant.applications') }}">
        <x-applicant-position-details :position="$position" :back-url="route('applicant.positions')" />
    </div>
@endsection
