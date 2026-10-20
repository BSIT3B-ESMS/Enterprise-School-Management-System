@extends('layouts.guest')

@section('title', 'Forgot Password · '.config('app.name'))

@section('content')
    <x-auth-card title="Forgot your password?"
        subtitle="PLP HR resets staff passwords. There is no self-service reset by email.">
        <div class="alert alert-info mb-0" role="alert">
            Email PLP HR at <a href="mailto:{{ $hrEmail }}">{{ $hrEmail }}</a> from your registered PLP email address.
        </div>

        <p class="mb-0">
            Include your full name and employee number so HR can find your account. HR will issue a temporary password,
            and you will choose a new one when you sign in.
        </p>

        <a class="btn btn-primary login-submit" href="mailto:{{ $hrEmail }}">Email PLP HR</a>
        <a class="btn btn-link p-0 text-center" href="{{ route('login') }}">Back to sign in</a>
    </x-auth-card>
@endsection
