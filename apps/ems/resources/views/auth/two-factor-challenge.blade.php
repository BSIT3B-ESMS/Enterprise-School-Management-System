@extends('layouts.guest')

@section('title', 'Two-Factor Code · '.config('app.name'))

@section('content')
    <x-auth-card title="Two-factor authentication"
        subtitle="Enter the 6-digit code from your authenticator app, or one of your recovery codes.">
        <form method="post" action="{{ route('two-factor.login') }}" class="d-grid gap-3" novalidate>
            @csrf

            <div>
                <label for="code" class="form-label">Authentication code</label>
                <input type="text" class="form-control tabular-nums" id="code" name="code" inputmode="numeric"
                    autocomplete="one-time-code" autofocus>
            </div>

            <div>
                <label for="recovery_code" class="form-label">Or a recovery code</label>
                <input type="text" class="form-control" id="recovery_code" name="recovery_code" autocomplete="off">
                <div class="form-text">A recovery code works once. Use it only if you lost your authenticator app.</div>
            </div>

            <button type="submit" class="btn btn-primary login-submit">Verify</button>
        </form>
    </x-auth-card>
@endsection
