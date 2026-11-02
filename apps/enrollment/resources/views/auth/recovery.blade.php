

@extends('layouts.app')

@section('title','Password recovery')

@section('content')
<div class="card border-0 shadow-sm p-4 mx-auto" style="max-width:540px"><h1 class="h3">Password recovery</h1><form method="post" action="{{ isset($token)?'/reset-password':'/forgot-password' }}">@csrf

@if(isset($token))<input type="hidden" name="token" value="{{ $token }}">

@endif
<label class="form-label" for="email">Account email</label><input class="form-control mb-3" name="email" id="email" type="email" value="{{ request('email',old('email')) }}" required autocomplete="username">

@if(isset($token))<label class="form-label" for="password">New password</label><input class="form-control mb-3" id="password" name="password" type="password" required autocomplete="new-password"><label class="form-label" for="password_confirmation">Confirm new password</label><input class="form-control mb-3" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"><p class="small text-secondary">At least 12 characters, with uppercase and lowercase letters and a number.</p>

@endif
<button class="btn btn-primary">{{ isset($token)?'Reset password':'Send recovery instructions' }}</button><a class="btn btn-link" href="/login">Back to sign in</a></form></div>

@endsection