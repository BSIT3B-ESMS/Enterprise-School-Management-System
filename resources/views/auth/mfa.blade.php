@extends('layouts.base')
@section('title','TAP | Account verification')
@section('body')
<main class="login-screen">
@include('auth.partials.brand')
<section class="login-panel"><div class="login-form">
<p class="small text-secondary mb-1">SECURE SIGN-IN</p>
<h2>{{ $enrolling ? 'Set up authenticator' : 'Verify sign-in' }}</h2>
@include('app.messages')
@if($enrolling)
<p class="small">Add the following setup key to your authenticator application.</p>
<code class="d-block p-3 bg-light text-break user-select-all">{{ $secret }}</code>
<p class="small text-secondary mt-2">For {{ auth()->user()->login_id ?? auth()->user()->email }}. Time-based 6-digit codes, changing every 30 seconds. Keep this key private.</p>
@else
<p class="login-sub">Enter an unused code from your authenticator or a recovery code.</p>
@endif
<form method="post" action="/mfa">@csrf
<label class="form-label w-100">Authenticator or recovery code<input class="form-control mt-1" name="code" required autofocus autocomplete="one-time-code"></label>
<button class="btn btn-primary w-100 mt-3" type="submit">Verify</button></form>
<form method="post" action="/logout" class="mt-3 text-center">@csrf<button type="submit" class="btn btn-link p-0">Sign out</button></form>
</div></section>
</main>
@endsection
