@extends('layouts.public')

@section('title', 'Position Details · '.config('app.name'))

{{--
    One position's details on the public Careers website, for anyone to read. Apply Now shows the Terms and
    Conditions first and, once they are accepted, opens the registration form with the position kept; declining
    returns to Open Positions (resources/js/applicant-positions.js).
--}}
@section('content')
    <div data-page="applicant-position" data-positions-context="public" data-register-url="{{ route('applicant.register') }}"
        data-careers-url="{{ route('careers.index') }}" data-apply-url="{{ route('applicant.apply') }}" data-application-url="{{ route('applicant.applications') }}">
        <x-applicant-position-details :position="$position" :back-url="route('careers.index')" />
    </div>
@endsection
