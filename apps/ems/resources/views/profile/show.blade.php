@extends('layouts.admin')

@section('title', 'My Profile · '.config('app.name'))

@php($user = auth()->user())

@section('content')
    <div class="d-grid gap-4" id="security">
        <div>
            <h1 class="page-title">My Profile</h1>
            <p class="text-body-secondary mt-1 mb-0">Your sign-in account.</p>
        </div>

        <section class="card employee-form-card">
            <dl class="row mb-0">
                <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $user->name }}</dd>
                <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $user->email }}</dd>
                <dt class="col-sm-3">Role</dt><dd class="col-sm-9">{{ $user->roleLabel() }}</dd>
                <dt class="col-sm-3">Last signed in</dt><dd class="col-sm-9">{{ $user->last_login_at?->format('M j, Y g:i A') ?? 'This is your first session' }}</dd>
                @if ($user->employee)
                    <dt class="col-sm-3">Employee record</dt><dd class="col-sm-9"><a href="{{ route('employees.show', $user->employee) }}">{{ $user->employee->employee_no }}</a></dd>
                @endif
            </dl>
        </section>

        <section class="card employee-form-card" aria-labelledby="security-title">
            <h2 class="employee-form-section" id="security-title">Security</h2>
            <div class="d-flex flex-wrap gap-3">
                <a href="{{ route('password.change') }}">Change my password</a>
                <a href="{{ route('two-factor.setup') }}">Authenticator app (two-factor)</a>
            </div>
        </section>
    </div>
@endsection
