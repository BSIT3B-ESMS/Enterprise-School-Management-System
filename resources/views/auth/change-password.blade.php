@extends('layouts.guest')

@section('title', 'Change Password · '.config('app.name'))

@section('content')
    <x-auth-card title="Choose a new password"
        :subtitle="$isForced
            ? 'Your first password is your employee ID. Choose your own password to continue.'
            : 'Enter your current password, then a new one.'">
        <form method="post" action="{{ route('password.change.update') }}" class="d-grid gap-3" novalidate>
            @csrf
            @method('PUT')

            <x-password-field name="current_password" label="Current password" autocomplete="current-password" required />

            <x-password-field name="password" label="New password" hint="At least 8 characters, with an uppercase letter, a lowercase letter, and a number. Easy passwords are rejected." required />

            <x-password-field name="password_confirmation" label="Confirm new password" required />

            <button type="submit" class="btn btn-primary login-submit">Change Password</button>
        </form>

        <form method="post" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="btn btn-link p-0">Sign out</button>
        </form>
    </x-auth-card>
@endsection
