@extends('layouts.guest')

@section('title', 'Page Not Found · '.config('app.name'))

{{-- Shown for any address the EMS does not have, with ways back to the EMS Login and to PLP Careers. --}}
@section('content')
    <div class="row g-0 min-vh-100">
        <x-login-brand tagline="The page you were looking for is not here." />

        <section class="col-md-6 d-flex align-items-center justify-content-center px-3 py-5">
            <div class="login-form w-100 d-grid gap-3">
                <p class="text-body-secondary mb-0">Error 404</p>
                <h2 class="login-form-title mb-0">Page not found</h2>
                <p class="text-body-secondary mb-0">
                    The address may be mistyped, or the page may have moved. Use one of the links below to continue.
                </p>
                <a class="btn btn-primary login-submit" href="{{ route('home') }}">Go to EMS Login</a>
                <a class="btn btn-outline-primary login-submit" href="{{ route('careers.index') }}">Go to PLP Careers</a>
            </div>
        </section>
    </div>
@endsection
