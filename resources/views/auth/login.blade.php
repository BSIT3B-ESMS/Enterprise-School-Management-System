

@extends('layouts.app')

@section('title','Sign in')

@section('content')
<div class="row align-items-center g-5 py-lg-5"><div class="col-lg-7"><span class="eyebrow">PLP STUDENT SERVICES</span><h1 class="display-4 fw-bold mt-3">Your next semester<br>starts here.</h1><p class="lead text-secondary mt-4">Access your enrollment, review your academic requirements, and keep track of your registration.</p><p class="text-secondary">Students use the account provided through Admission. For account assistance, contact the Registrar.</p></div><div class="col-lg-5"><div class="card border-0 shadow-sm p-4 p-lg-5"><h2 class="h3">Welcome back</h2><p class="text-secondary">Sign in to your enrollment account.</p><form method="post" action="/login">@csrf<label for="email" class="form-label mt-3">Email address</label><input id="email" name="email" type="email" class="form-control form-control-lg" value="{{ old('email') }}" required autocomplete="username"><label for="password" class="form-label mt-3">Password</label><input id="password" name="password" type="password" class="form-control form-control-lg" required autocomplete="current-password"><button class="btn btn-primary btn-lg w-100 mt-4">Sign in</button><a href="/forgot-password" class="btn btn-link w-100 mt-2">Forgot password?</a></form></div></div></div>

@endsection