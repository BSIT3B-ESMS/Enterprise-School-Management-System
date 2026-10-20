@extends('layouts.employee')

@section('title', 'Benefits · '.config('app.name'))

@section('content')
    <div class="d-grid gap-4">
        <div>
            <h1 class="page-title">Benefits</h1>
            <p class="text-body-secondary mt-1 mb-0">What HR included for you, and what else PLP offers.</p>
        </div>

        <section aria-labelledby="mine-title">
            <h2 class="fs-5 mb-3" id="mine-title">Your benefits <span class="ems-chip tabular-nums">{{ $mine->count() }}</span></h2>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                @forelse ($mine as $benefit)
                    <div class="col"><article class="card dashboard-panel h-100"><h3 class="fs-6">{{ $benefit->name }}</h3><p class="mb-1">{{ $benefit->description }}</p><small class="text-body-secondary">Since {{ \Illuminate\Support\Carbon::parse($benefit->pivot->granted_on)->format('M j, Y') }}</small></article></div>
                @empty
                    <div class="col-12"><p class="text-body-secondary">None are included for you yet. Ask HR.</p></div>
                @endforelse
            </div>
        </section>

        <section aria-labelledby="other-title">
            <h2 class="fs-5 mb-1" id="other-title">Other benefits</h2>
            <p class="dashboard-panel-subtitle">Not included for you at the moment.</p>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                @foreach ($others as $benefit)
                    <div class="col"><article class="card dashboard-panel h-100"><h3 class="fs-6">{{ $benefit->name }}</h3><p class="mb-0">{{ $benefit->description }}</p>@if ($benefit->full_time_only)<small class="text-body-secondary">Full-time staff</small>@endif</article></div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
