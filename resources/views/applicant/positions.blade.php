@extends('layouts.applicant')

@section('title', 'Open Positions · '.config('app.name'))

@section('content')
    <div class="d-grid gap-4">
        <div>
            <h1 class="page-title">Open Positions</h1>
            <p class="text-body-secondary mt-1 mb-0">Browse the open positions and apply for the ones that suit you.</p>
        </div>

        @include('careers._list')
    </div>
@endsection
