@extends('layouts.guest')

@section('title', 'Two-Factor Authentication · '.config('app.name'))

@php
    $isRequired = config('ems.require_mfa') && $user->isHr();
    $statuses = [
        'two-factor-authentication-enabled' => 'Scan the code below with your authenticator app, then enter the 6-digit code to finish.',
        'two-factor-authentication-confirmed' => 'Two-factor authentication is on. Save your recovery codes now.',
        'recovery-codes-generated' => 'New recovery codes were generated. The old ones no longer work.',
        'two-factor-authentication-disabled' => 'Two-factor authentication was turned off.',
    ];
@endphp

@section('content')
    <x-auth-card title="Two-factor authentication"
        :subtitle="$isRequired ? 'HR accounts must use an authenticator app (Google Authenticator, Authy, or similar).' : 'Add a second step to your sign-in with an authenticator app.'">
        @if (session('status') && isset($statuses[session('status')]))
            <div class="alert alert-success mb-0" role="status">{{ $statuses[session('status')] }}</div>
        @endif

        @if (! $user->two_factor_secret)
            <form method="post" action="{{ route('two-factor.enable') }}">
                @csrf
                <button type="submit" class="btn btn-primary login-submit w-100">Set up authenticator app</button>
            </form>
        @elseif (! $user->two_factor_confirmed_at)
            <div class="text-center" role="img" aria-label="QR code for your authenticator app">
                {!! $user->twoFactorQrCodeSvg() !!}
            </div>

            <p class="mb-0">
                Can't scan it? Enter this key in your app:
                <code class="user-select-all">{{ decrypt($user->two_factor_secret) }}</code>
            </p>

            <form method="post" action="{{ route('two-factor.confirm') }}" class="d-grid gap-3" novalidate>
                @csrf

                <div>
                    <label for="code" class="form-label">6-digit code</label>
                    <input type="text" class="form-control tabular-nums" id="code" name="code" inputmode="numeric"
                        autocomplete="one-time-code" required>
                </div>

                <button type="submit" class="btn btn-primary login-submit">Confirm</button>
            </form>
        @else
            <div class="alert alert-success mb-0" role="status">Two-factor authentication is on for this account.</div>

            <div>
                <h2 class="fs-6">Recovery codes</h2>
                <p class="text-body-secondary">
                    Keep these somewhere safe. Each works once if you lose your authenticator app.
                </p>
                <ul class="list-unstyled font-monospace mb-0">
                    @foreach ($user->recoveryCodes() as $recoveryCode)
                        <li>{{ $recoveryCode }}</li>
                    @endforeach
                </ul>
            </div>

            <form method="post" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary w-100">Generate new recovery codes</button>
            </form>

            @unless ($isRequired)
                <form method="post" action="{{ route('two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">Turn off two-factor authentication</button>
                </form>
            @endunless

            <a class="btn btn-primary login-submit" href="{{ $user->homeUrl() }}">Continue</a>
        @endif

        <form method="post" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="btn btn-link p-0">Sign out</button>
        </form>
    </x-auth-card>
@endsection
