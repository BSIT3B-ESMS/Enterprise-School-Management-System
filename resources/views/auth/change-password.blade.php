@extends('layouts.guest')

@section('title', 'Change Password · '.config('app.name'))

@section('content')
    <x-auth-card title="Choose a new password"
        :subtitle="$isForced
            ? 'You signed in with a temporary password. Choose your own password to continue.'
            : 'Enter your current password, then a new one.'">
        <form method="post" action="{{ route('password.change.update') }}" class="d-grid gap-3" novalidate>
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="form-label">Current password</label>
                <input type="password" class="form-control" id="current_password" name="current_password"
                    autocomplete="current-password" required>
            </div>

            <div>
                <label for="password" class="form-label">New password</label>
                <input type="password" class="form-control" id="password" name="password" autocomplete="new-password"
                    aria-describedby="password-hint" required>
                <div class="form-text" id="password-hint">
                    At least 12 characters. Passwords found in known data breaches are rejected.
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="form-label">Confirm new password</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                    autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-primary login-submit">Change Password</button>
        </form>

        <form method="post" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="btn btn-link p-0">Sign out</button>
        </form>
    </x-auth-card>
@endsection
