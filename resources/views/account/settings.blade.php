@extends('layouts.admin')

@section('title', 'Account Settings · '.config('app.name'))

@section('content')
    <div class="d-grid gap-4">
        <div>
            <h1 class="page-title">Account Settings</h1>
            <p class="text-body-secondary mt-1 mb-0">Appearance and security.</p>
        </div>

        <section class="card employee-form-card" aria-labelledby="appearance-title">
            <h2 class="employee-form-section" id="appearance-title">Appearance</h2>
            <p class="text-body-secondary">Switch between the light and dark theme. The choice is kept in this browser.</p>
            <div><button type="button" class="btn btn-outline-secondary" data-theme-toggle>Switch theme</button></div>
        </section>

        <section class="card employee-form-card" aria-labelledby="security-title">
            <h2 class="employee-form-section" id="security-title">Security</h2>
            <div class="d-flex flex-wrap gap-3">
                <a href="{{ route('password.change') }}">Change my password</a>
                <a href="{{ route('two-factor.setup') }}">Authenticator app (two-factor)</a>
            </div>
        </section>

        <section class="card employee-form-card" aria-labelledby="notif-title">
            <h2 class="employee-form-section" id="notif-title">Notifications</h2>
            <p class="mb-0">Notifications appear inside the system (the bell at the top). Nothing is sent to your email.
                <a href="{{ route('notifications.index') }}">View my notifications</a>.</p>
        </section>
    </div>
@endsection
