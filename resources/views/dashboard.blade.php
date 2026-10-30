

@extends('layouts.app')

@section('title',$portal.' Portal')

@section('content')

<div class="d-flex justify-content-between flex-wrap gap-3 align-items-start mb-5"><div><span class="eyebrow">{{ strtoupper($portal) }} PORTAL</span><h1 class="mt-2">Welcome, {{ auth()->user()->name }}</h1><p class="text-secondary">{{ $description }}</p></div><a class="btn btn-outline-secondary" href="{{ route('password.edit') }}">Account settings</a></div>
<div class="row g-3 mb-4">

@foreach($metrics as $label=>$value)
<div class="col-6 col-lg-3"><div class="card border-0 shadow-sm p-4 h-100"><span class="text-secondary small">{{ $label }}</span><strong class="display-6 mt-2">{{ $value }}</strong></div></div>

@endforeach
</div><div class="card border-0 shadow-sm p-4 p-lg-5"><span class="eyebrow">ACCOUNT READY</span><h2 class="h4 mt-3">Enrollment services</h2><p class="text-secondary mb-0">{{ auth()->user()->role==='registrar'?'Maintain academic records, import approved exports, prepare schedules, and process accepted enrollment.':(auth()->user()->role==='department_head'?'Review academic records and evaluation requests for your assigned programs, then prepare special academic proposals.':'Review your notifications, academic requirements, enrollment proposals, and registration records.') }}</p></div>

@endsection