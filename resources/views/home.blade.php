@extends('layouts.app')

@section('title', config('app.name'))

@section('content')
    <h1 class="h3">{{ config('app.name') }}</h1>

    <p class="text-body-secondary">
        Frontend baseline: Bootstrap 5 via Sass, bundled by Vite.
    </p>
@endsection
