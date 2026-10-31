@extends('layouts.base')
@section('title','TAP | Sign in')
@section('body')
  <!-- LOGIN -->
  <div id="loginScreen" class="login-screen">
    @include('auth.partials.brand')
    <section class="login-panel">
      <form id="formLogin" class="login-form" method="post" action="/login">
        <h2>Welcome back</h2>
        @include('app.messages')
        @csrf
        <p class="login-sub">Sign in with your employee ID or administrator ID.</p>
        <label class="form-label" for="loginUsername">Employee ID / Admin ID</label>
        <div class="field mb-3"><i class="bi bi-person"></i>
          <input type="text" id="loginUsername" name="identifier" value="{{ old('identifier') }}" class="form-control" required autofocus autocomplete="username">
        </div>
        <label class="form-label" for="loginPassword">Password</label>
        <div class="field mb-4"><i class="bi bi-lock"></i>
          <input type="password" id="loginPassword" name="password" class="form-control" required autocomplete="current-password">
          <button type="button" class="eye" id="btnEye" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100 fs-6">Sign in</button>
        <a class="d-block mt-3 small text-center" href="/kiosk">Open attendance kiosk</a>
      </form>
    </section>
  </div>


@endsection
@push('scripts')<script>document.getElementById('btnEye')?.addEventListener('click',function(){const p=document.getElementById('loginPassword');const visible=p.type==='password';p.type=visible?'text':'password';this.setAttribute('aria-label',visible?'Hide password':'Show password');this.querySelector('i').className=visible?'bi bi-eye-slash':'bi bi-eye';});</script>@endpush
