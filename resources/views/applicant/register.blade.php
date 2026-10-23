@extends('layouts.guest')

@section('title', 'Create an Applicant Account · '.config('app.name'))

{{--
    The Create Account address (what Create Account and Apply Now lead to, after the Terms and Conditions): the same
    Applicant Portal page as /applicant/portal, opened on its Create Account form. Declining the terms returns to the
    previous page (resources/js/applicant-auth.js).
--}}
@section('content')
    <x-applicant-portal initial="register" />
@endsection
