@extends('layouts.base')
@section('title','TAP | First-time setup')
@section('body')
<main class="login-screen">
@include('auth.partials.brand')
<section class="login-panel"><form method="post" action="/setup" class="login-form">@csrf
<p class="small text-secondary mb-1">FIRST-TIME SETUP</p><h2>Create administrator</h2>
<p class="login-sub">Choose an administrator ID and password. No email is required.</p>
@include('app.messages')
<label class="form-label w-100">Full name<input class="form-control mt-1" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name"></label>
<label class="form-label w-100 mt-3">Admin ID<input class="form-control mt-1" type="text" name="admin_id" value="{{ old('admin_id', 'ADMIN-001') }}" required maxlength="50" pattern="ADMIN-[A-Za-z0-9_-]+" autocomplete="username"></label>
<label class="form-label w-100 mt-3">Password<input class="form-control mt-1" type="password" name="password" required minlength="12" autocomplete="new-password"></label>
<p class="small text-secondary mt-1">At least 12 characters, including letters and numbers.</p>
<label class="form-label w-100">Confirm password<input class="form-control mt-1" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></label>
<button class="btn btn-primary w-100 mt-3" type="submit">Create account</button>
</form></section>
</main>
@endsection
