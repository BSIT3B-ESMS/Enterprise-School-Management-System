<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>@yield('title','Enrollment') · PLP</title><link href="{{ asset('vendor/bootstrap.min.css') }}" rel="stylesheet"><link href="{{ asset('css/app.css') }}" rel="stylesheet"></head>
<body class="{{ auth()->check() ? 'authenticated' : 'guest' }}"><a class="skip-link" href="#main-content">Skip to content</a><header class="topbar"><a href="/" class="brand"><img src="{{ asset('branding/plp-seal.png') }}" width="48" height="48" alt="PLP seal"><span>Pamantasan ng Lungsod ng Pasig<small>Enrollment Management System</small></span></a>

@auth<div class="account-tools"><button class="btn btn-sm btn-outline-light d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#portal-sidebar" aria-controls="portal-sidebar">Menu</button><span class="account-name">{{ auth()->user()->name }}<small>{{ ucwords(str_replace('_',' ',auth()->user()->role)) }}</small></span><form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-light">Sign out</button></form></div>

@endauth</header>

@auth
<aside class="offcanvas-lg offcanvas-start portal-sidebar d-print-none" tabindex="-1" id="portal-sidebar" aria-labelledby="portal-menu-title"><div class="offcanvas-header"><h2 class="offcanvas-title h5" id="portal-menu-title">Enrollment menu</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#portal-sidebar" aria-label="Close menu"></button></div><div class="p-3 sidebar-content">

@include('partials.navigation')</div></aside>

@endauth
<main id="main-content" tabindex="-1" class="container-fluid portal-main py-5">

@if(session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>

@endif

@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">

@foreach($errors->all() as $error)<li>{{ $error }}</li>

@endforeach</ul></div>

@endif
@yield('content')</main><script src="{{ asset('vendor/jquery.min.js') }}"></script><script src="{{ asset('vendor/bootstrap.bundle.min.js') }}"></script>@stack('scripts')</body></html>
