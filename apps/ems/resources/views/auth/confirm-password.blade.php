@extends('layouts.guest')

@section('title', 'Confirm Password · '.config('app.name'))

@section('content')
    <x-auth-card title="Confirm your password" subtitle="This is a sensitive action. Enter your password to continue.">
        <form method="post" action="{{ route('password.confirm') }}" class="d-grid gap-3" novalidate>
            @csrf

            <div>
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password"
                    autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn-primary login-submit">Confirm</button>
        </form>
    </x-auth-card>
@endsection
