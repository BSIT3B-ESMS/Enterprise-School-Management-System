@extends('layouts.app')
@section('title','Sign in')
@section('content')
<div class="login-layout">
    <section class="login-intro" aria-labelledby="intro-title">
        <h1 id="intro-title">Your next semester<br>starts here.</h1>
        <p class="login-description">Access your enrollment, review your academic requirements, and keep track of your registration.</p>
        <div class="login-help"><h2>First time signing in?</h2><p>Students use the account provided through Admission. For account assistance, contact the Registrar.</p></div>
    </section>
    <section class="login-panel" aria-labelledby="signin-title">
        <h2 id="signin-title">Welcome back</h2>
        <p class="text-secondary">Sign in to your enrollment account.</p>
        <form method="post" action="/login">
            @csrf
            <label for="email" class="form-label mt-3">Email address</label>
            <input id="email" name="email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="username" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<div id="email-error" class="invalid-feedback">{{ $message }}</div>@enderror
            <label for="password" class="form-label mt-4">Password</label>
            <div class="password-field"><input id="password" name="password" type="password" class="form-control form-control-lg" required autocomplete="current-password"><button type="button" class="password-toggle" aria-controls="password" aria-pressed="false">Show</button></div>
            <div class="text-end mt-3"><a href="/forgot-password">Forgot password?</a></div>
            <button class="btn btn-primary btn-lg w-100 mt-4">Sign in</button>
        </form>
    </section>
</div>
@endsection
@push('scripts')
<script>
document.querySelector('.password-toggle').addEventListener('click', function () {
    const input = document.getElementById('password');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    this.textContent = show ? 'Hide' : 'Show';
    this.setAttribute('aria-pressed', String(show));
});
</script>
@endpush
