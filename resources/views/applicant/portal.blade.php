@extends('layouts.guest')

@section('title', 'Applicant Portal · '.config('app.name'))

{{--
    The PLP Careers Applicant Portal (Apply on the Careers website opens it, with the chosen position kept in ?position=):
    Log In by default, with Create Account switching the form on the right (resources/views/components/applicant-portal.blade.php).
--}}
@section('content')
    <x-applicant-portal initial="login" />
@endsection
